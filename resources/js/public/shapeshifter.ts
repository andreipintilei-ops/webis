import type {
    Group,
    Line,
    Mesh,
    PerspectiveCamera,
    Quaternion,
    Scene,
    Vector3,
    WebGLRenderer,
} from 'three';
import { loadGsap, prefersReducedMotion } from '@/public/gsap';
import { getSmoother } from '@/public/smooth-scroll';

/**
 * The Shapeshifter (the home page; components/site/stage.blade.php,
 * css/site/variant-stage.css): one Webis object — five thin, matte plates,
 * gently cupped and softly edged — taking a form per chapter, each form an
 * abstract read of what the chapter says. No labels, no text; the shapes
 * carry it.
 *
 * - the stage's start: the pinwheel (S0), coming in as the stage does;
 * - 01 Software la comandă: an assembly — a base, two walls, a brace and a
 *   module stepping into place — over dashed blueprint guides (S1);
 * - 02 Produse SaaS: tiles fanned and ready, the pale one frosted and one
 *   still a wedge, inside a dotted ring with three points going round (S2);
 * - 03 Web și e-commerce: a screen — a rounded panel with a card and a
 *   triangle in front of it — on a tilted orbit (S3);
 * - the finale: the plates fly into a purple panel and stand as a W of five
 *   rounded squares (S4), and ride it off the screen.
 *
 * Each plate can change its outline: a rounded triangle (`m` 0) and a
 * rounded square (`m` 1) are the same mesh, sampled point for point along
 * their outlines, so either one is the other's morph target. A form picks
 * `m` per plate.
 *
 * Every form is measured once at startup and drawn to the same width on the
 * screen, so none of them reads bigger than the rest whatever its spread.
 *
 * Each form sits in the chapter's free half (`data-stage-slot`; right, left,
 * right), then in the panel's slot. Scrubbed tweens (ScrollTrigger, scrub 1)
 * move it between forms, slerping quaternions; it crosses to the next half on
 * the way. While a chapter is read it turns ±4° with the scroll and floats
 * slowly (6 s). No spin, no mouse-following, nothing that reacts to a hover.
 *
 * From 1024px it holds the middle of the screen in the chapter's half, coming
 * in with the stage's top, then settles into the panel and leaves with it.
 * Below, each chapter (and the panel) has a box: the object sits in it, and
 * as the next box comes up it flies there — in from the screen's top edge
 * (or from its own box, if that is still in view), swinging out to one side
 * and a little smaller on the way, behind the text — taking the next form as
 * it lands.
 *
 * Reduced motion: no scrub, no float — a 400 ms crossfade to the next form as
 * a chapter comes in. Without WebGL: a still of the pinwheel in chapter 01's
 * box, and the panel draws its W in CSS instead.
 *
 * It sits between the stage's ground and its content (the stacking rules in
 * variant-stage.css). Inside the smooth-scrolled content, where
 * position: fixed doesn't hold, it is held to the screen by countering the
 * content's transform. Renders only while the stage is in view and the tab is
 * visible.
 */

type Vec = [number, number, number];

interface Pose {
    p: Vec;
    /** Degrees, applied as: twist about the slab's own axis (Y), tilt (X),
     *  then spin in the screen's plane (Z). */
    r?: Vec;
    s?: Vec;
    /** The outline: 0 a rounded triangle, 1 a rounded square. */
    m?: number;
}

interface State {
    slabs: Pose[];
    /** The whole object's turn, in degrees. */
    turn: Vec;
}

/** The plates: size (to a corner), the colour at its core, halfway out, at
 *  its edge, and whether it is a frosted one. Darkest first, so the W reads
 *  as one gradient from left to right. */
const PIECES: [number, string, string, string, boolean][] = [
    [1.15, '#0E0B33', '#2A1E8E', '#6B4BFF', false],
    [1.06, '#2C2390', '#5B45E6', '#A07BFF', false],
    [1, '#5A3FE0', '#8E63F5', '#D08CF5', false],
    [0.94, '#B9ACFF', '#D9D1FF', '#F4F1FF', false],
    [0.9, '#C4B8F2', '#EDE9FF', '#FFFFFF', true],
];

// ---- The plate's mesh ------------------------------------------------------

const TAU = Math.PI * 2;
/** Samples around the outline, rings across a face, rings around the rim. */
const COLUMNS = 96;
const FACE = 3;
const RIM = 12;
const ROWS = 2 * FACE + RIM + 1;
/** Half the thickness — and the rim's radius, so the edge rolls right over
 *  (× the plate's size). Thin: these are plates, not blocks. */
const THICK = 0.045;
/** How much each plate is cupped (z = BEND · x²), as the reference's are. */
const BEND = 0.1;
const TRI_ROUND = 0.22;
const SQUARE_ROUND = 0.3;

const clamp01 = (v: number): number => Math.min(1, Math.max(0, v));
const smooth = (t: number): number => t * t * (3 - 2 * t);
const lerp = (a: number, b: number, t: number): number => a + (b - a) * t;
/** 1 at form `k`, 0 a form away. */
const near = (q: number, k: number): number =>
    smooth(clamp01(1 - Math.abs(q - k)));

/** 0 to 1 as an edge moves up the screen from `from` to `to` (px). */
const ramp = (edge: number, from: number, to: number): number =>
    clamp01((from - edge) / (from - to));

/**
 * A convex rounded regular polygon, resampled at equal arc length going
 * counter-clockwise from 12 o'clock: the points, then their outward normals.
 * Two shapes sampled this way share a point-for-point mapping, which is what
 * lets either one morph into the other.
 */
const outlineOf = (
    sides: number,
    size: number,
    round: number,
    phase: number,
): { p: Float64Array; n: Float64Array } => {
    // The boundary: an arc per corner, spanning that corner's exterior angle;
    // the straight edges are the jump from one arc's end to the next's start.
    const ARC = 20;
    const radius = size * round;
    const inset = radius / Math.cos(Math.PI / sides);
    const half = Math.PI / sides;
    const dense: number[] = [];

    for (let i = 0; i < sides; i++) {
        const corner = phase + (i * TAU) / sides;
        const cx = Math.cos(corner) * (size - inset);
        const cy = Math.sin(corner) * (size - inset);

        for (let j = 0; j <= ARC; j++) {
            const a = corner - half + (2 * half * j) / ARC;

            dense.push(cx + Math.cos(a) * radius, cy + Math.sin(a) * radius);
        }
    }

    // How far along it each point sits, and where it crosses 12 o'clock
    // (going counter-clockwise, x falls through 0 in the upper half).
    const count = dense.length / 2;
    const at = new Float64Array(count + 1);
    let start = 0;

    for (let i = 0; i < count; i++) {
        const j = (i + 1) % count;
        const ax = dense[i * 2]!;
        const ay = dense[i * 2 + 1]!;
        const bx = dense[j * 2]!;
        const by = dense[j * 2 + 1]!;

        at[i + 1] = at[i]! + Math.hypot(bx - ax, by - ay);

        if (ax >= 0 && bx < 0 && (ay > 0 || by > 0)) {
            start = at[i]! + (ax / (ax - bx)) * (at[i + 1]! - at[i]!);
        }
    }

    const total = at[count]!;
    const p = new Float64Array(COLUMNS * 2);

    for (let k = 0; k < COLUMNS; k++) {
        const s = (start + (k * total) / COLUMNS) % total;
        let i = 0;

        while (i < count - 1 && at[i + 1]! < s) {
            i++;
        }

        const j = (i + 1) % count;
        const span = at[i + 1]! - at[i]!;
        const t = span > 1e-9 ? (s - at[i]!) / span : 0;

        p[k * 2] = lerp(dense[i * 2]!, dense[j * 2]!, t);
        p[k * 2 + 1] = lerp(dense[i * 2 + 1]!, dense[j * 2 + 1]!, t);
    }

    // Outward normals, from the ring itself: counter-clockwise, that is
    // (dy, -dx) across each point's neighbours.
    const n = new Float64Array(COLUMNS * 2);

    for (let k = 0; k < COLUMNS; k++) {
        const a = (k + COLUMNS - 1) % COLUMNS;
        const b = (k + 1) % COLUMNS;
        const dx = p[b * 2]! - p[a * 2]!;
        const dy = p[b * 2 + 1]! - p[a * 2 + 1]!;
        const length = Math.hypot(dx, dy) || 1;

        n[k * 2] = dy / length;
        n[k * 2 + 1] = -dx / length;
    }

    return { p, n };
};

/** The rows: out across the front face, round the rim, back in across the
 *  back face. `u` is how far out on a face, `phi` where it is on the rim. */
const ROW_U: number[] = [];
const ROW_PHI: number[] = [];

for (let j = 0; j <= FACE; j++) {
    ROW_U.push(j / FACE);
    ROW_PHI.push(0);
}

for (let j = 1; j < RIM; j++) {
    ROW_U.push(1);
    ROW_PHI.push((j / RIM) * Math.PI);
}

for (let j = FACE; j >= 0; j--) {
    ROW_U.push(j / FACE);
    ROW_PHI.push(Math.PI);
}

/** One outline rolled into a thick plate: a flat face, an edge rolled right
 *  over, a flat back. The same grid whatever the outline, so two of these
 *  morph into each other. */
const slabOf = (
    outline: { p: Float64Array; n: Float64Array },
    h: number,
): { position: Float32Array; normal: Float32Array } => {
    const position = new Float32Array(ROWS * COLUMNS * 3);
    const normal = new Float32Array(ROWS * COLUMNS * 3);
    let o = 0;

    for (let j = 0; j < ROWS; j++) {
        const u = ROW_U[j]!;
        const sin = Math.sin(ROW_PHI[j]!);
        const cos = Math.cos(ROW_PHI[j]!);

        for (let k = 0; k < COLUMNS; k++) {
            const nx = outline.n[k * 2]!;
            const ny = outline.n[k * 2 + 1]!;
            // The core: the outline pulled in by the rim's radius.
            const cx = outline.p[k * 2]! - nx * h;
            const cy = outline.p[k * 2 + 1]! - ny * h;

            const x = cx * u + nx * h * sin;
            const y = cy * u + ny * h * sin;
            // Cupped like a sheet (z = BEND · x²), its normals turned with
            // it. Applied the same way to either outline, so the two still
            // match point for point and can morph into each other.
            const phi = -Math.atan(2 * BEND * x);
            const rx = nx * sin;
            const rz = cos;

            position[o] = x;
            position[o + 1] = y;
            position[o + 2] = h * cos + BEND * x * x;
            normal[o] = rx * Math.cos(phi) + rz * Math.sin(phi);
            normal[o + 1] = ny * sin;
            normal[o + 2] = -rx * Math.sin(phi) + rz * Math.cos(phi);
            o += 3;
        }
    }

    return { position, normal };
};

const SLAB_INDEX: number[] = [];

for (let j = 0; j < ROWS - 1; j++) {
    for (let k = 0; k < COLUMNS; k++) {
        const next = (k + 1) % COLUMNS;
        const a = j * COLUMNS + k;
        const b = j * COLUMNS + next;
        const c = (j + 1) * COLUMNS + k;
        const d = (j + 1) * COLUMNS + next;

        SLAB_INDEX.push(a, c, b, b, c, d);
    }
}

// ---- The forms -------------------------------------------------------------

/** The pinwheel: tiles and wedges pointing out, each twisted about its own
 *  axis. */
const PINWHEEL: Pose[] = [
    { p: [0.02, -0.32, 0.24], r: [10, 48, 185] },
    { p: [0.28, 0.3, -0.06], r: [-8, -42, -35], m: 1 },
    { p: [-0.38, 0.06, 0.04], r: [6, 52, 95], m: 1 },
    { p: [0.34, -0.16, -0.24], r: [-6, -50, -118] },
    { p: [-0.12, 0.46, 0.3], r: [12, 30, 25], s: [0.62, 0.62, 1], m: 1 },
];

/** The W: five rounded squares on their corners, up–down–up–down–up. */
const W_STEP = 1.08;
const W_RISE = 0.54;
const W_SIZE = 0.9;
const W: Pose[] = PIECES.map(([size], i) => {
    const up = i % 2 === 0;
    const k = W_SIZE / size;

    return {
        p: [(i - 2) * W_STEP, up ? W_RISE : -W_RISE, up ? 0 : -0.05],
        r: [up ? -3 : 3, (i - 2) * 3, 45],
        s: [k, k, 1],
        m: 1,
    };
});

/** S0 the pinwheel, S1–S3 the chapters' forms, S4 the W on the panel. */
const STATES: State[] = [
    { turn: [8, -12, 0], slabs: PINWHEEL },
    // 01 Software la comandă: an assembly going up, piece by piece.
    {
        turn: [15, -33, 0],
        slabs: [
            // The floor it is built on.
            { p: [0, -0.8, 0.06], r: [-90, 0, 0], s: [1.12, 1.12, 1], m: 1 },
            // The back wall, then the side wall square to it.
            { p: [0.06, 0.22, -0.74], r: [0, 0, 0], s: [1, 1.05, 1], m: 1 },
            { p: [-0.84, 0.16, 0.08], r: [0, 90, 0], s: [0.72, 1, 1], m: 1 },
            // A brace across the corner.
            { p: [0.32, -0.3, 0.44], r: [-20, 40, 26], s: [0.62, 0.62, 1] },
            // The piece still coming in.
            {
                p: [1.02, 0.84, 0.64],
                r: [-16, -28, 8],
                s: [0.42, 0.42, 1],
                m: 1,
            },
        ],
    },
    // 02 Produse SaaS: tiles fanned and ready to run, one still a wedge; two
    // tucked behind.
    {
        turn: [-6, -12, 0],
        slabs: [
            { p: [0.16, -0.62, 0.34], r: [-14, 16, 142], s: [1.06, 1.06, 1] },
            {
                p: [-1.08, 1.16, 0.2],
                r: [10, -20, 30],
                s: [0.3, 0.3, 1],
                m: 1,
            },
            {
                p: [0.6, 0.46, -0.14],
                r: [10, -16, -34],
                s: [1.02, 1.02, 1],
                m: 1,
            },
            {
                p: [0.98, -0.56, -0.62],
                r: [-8, 22, -16],
                s: [0.34, 0.34, 1],
                m: 1,
            },
            {
                p: [-0.72, 0, 0.14],
                r: [-10, 24, 18],
                s: [1.1, 1.1, 1],
                m: 1,
            },
        ],
    },
    // 03 Web și e-commerce: a screen, a card and a triangle over it, stacked
    // up the frame rather than along it, so the form reads close to square.
    {
        turn: [4, -14, 0],
        slabs: [
            // A small triangle, low and to the left.
            { p: [-0.78, -0.82, 0.3], r: [-6, 20, 128], s: [0.42, 0.42, 1] },
            // The screen, up and to the left.
            {
                p: [-0.34, 0.52, -0.34],
                r: [2, -16, 0],
                s: [1.52, 0.9, 1],
                m: 1,
            },
            // The triangle in front, low and to the right.
            { p: [0.38, -0.66, 0.46], r: [-6, 14, -118], s: [0.86, 0.86, 1] },
            // A small square, high and to the right.
            { p: [0.86, 0.9, -0.46], r: [0, -14, 0], s: [0.4, 0.4, 1], m: 1 },
            // The frosted card, offset down and right of the screen.
            { p: [0.3, -0.04, 0.12], r: [0, -4, 0], s: [1.34, 0.86, 1], m: 1 },
        ],
    },
    { turn: [2, -3, 0], slabs: W },
];

/** How the ring around it sits per form: tilt about X, roll about Z. */
const RING: [number, number][] = [
    [20, 0],
    [66, 16],
    [14, 4],
    [52, -10],
    [30, 0],
];

// ---- How big it is drawn ---------------------------------------------------

/** A chapter's form fills this much of its half of the screen, capped
 *  against the screen's height; then the same for the panel and the boxes. */
const CHAPTER_FILL = 0.86;
const CHAPTER_CAP = 0.66;
const PANEL_FILL = 0.37;
const PANEL_CAP = 1.3;
/** On phones and tablets the panel fills more of its width: it is the only
 *  thing on the screen there. */
const PANEL_FILL_NARROW = 0.78;
const BOX_FILL = 0.8;
const PHONE_FILL = 0.95;
/**
 * Phones and tablets, between boxes: the flight runs while the next box's top
 * rises from `from` to `to` screens; on the way the object swings out to the
 * side by `swing` of the screen's width and shrinks by `shrink` at the most.
 */
const FLIGHT = { from: 1.35, to: 0.55, swing: 0.22, shrink: 0.25 };

const hasWebGL = (): boolean => {
    try {
        const canvas = document.createElement('canvas');

        return !!(canvas.getContext('webgl2') ?? canvas.getContext('webgl'));
    } catch {
        return false;
    }
};

export async function initShapeshifter(): Promise<void> {
    const host = document.querySelector<HTMLElement>('[data-shapeshifter]');
    const stage = document.querySelector<HTMLElement>('[data-stage]');
    const panel = document.querySelector<HTMLElement>('[data-stage-finale]');
    const panelSlot = panel?.querySelector<HTMLElement>('[data-stage-slot]');
    const chapters = Array.from(
        document.querySelectorAll<HTMLElement>('[data-stage-chapter]'),
    ).flatMap((el) => {
        const slot = el.querySelector<HTMLElement>('[data-stage-slot]');

        return slot ? [{ el, slot }] : [];
    });

    if (!host || !stage || !panel || !panelSlot || chapters.length !== 3) {
        return;
    }

    /** The four homes it visits: a chapter's half each, then the panel's. */
    const slots = [...chapters.map(({ slot }) => slot), panelSlot];

    /** Without WebGL: the object as a still, and the panel's W drawn in CSS. */
    const showStill = (): void => {
        const still = document.createElement('img');

        still.src = '/images/shapeshifter/overview.png';
        still.alt = '';
        still.className = 'shapeshifter-still';
        chapters[0]?.slot.append(still);
        panel.classList.add('stage-finale--still');
    };

    if (!hasWebGL()) {
        showStill();

        return;
    }

    const reduced = prefersReducedMotion();

    const [THREE, { RoomEnvironment }, gsap, { ScrollTrigger }] =
        await Promise.all([
            import('three'),
            import('three/addons/environments/RoomEnvironment.js'),
            loadGsap(),
            import('gsap/ScrollTrigger'),
        ]);

    gsap.registerPlugin(ScrollTrigger);

    // ---- The scene ---------------------------------------------------------

    let renderer: WebGLRenderer;

    try {
        renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'high-performance',
        });
    } catch {
        showStill();

        return;
    }

    renderer.setClearColor(0x000000, 0);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1;
    host.append(renderer.domElement);

    const scene: Scene = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);

    // Soft reflections from a room: what makes it look rendered.
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environmentIntensity = 0.7;

    // A long lens: graphic, not a 3D demo.
    const camera: PerspectiveCamera = new THREE.PerspectiveCamera(
        18,
        1,
        0.1,
        100,
    );

    camera.position.set(0, 0, 30);

    const hemi = new THREE.HemisphereLight(0xf5f3ff, 0x1f2868, 1.05);
    const key = new THREE.DirectionalLight(0xffffff, 1.45);
    const rim = new THREE.DirectionalLight(0xa44cee, 1.5);

    key.position.set(-5, 7, 8);
    rim.position.set(6, -3, -7);
    scene.add(hemi, key, rim);

    // ---- The slabs ---------------------------------------------------------

    const object = new THREE.Group();
    /** Per slab, a ring of points on each outline: what each form is measured
     *  by, so every form is drawn to the same width. */
    const silhouettes: Float64Array[] = [];
    const STEP = 8;

    const slabs: Mesh[] = PIECES.map(([size, inner, mid, outer, frosted]) => {
        const h = size * THICK;
        const triangle = outlineOf(3, size, TRI_ROUND, Math.PI / 2);
        const square = outlineOf(4, size, SQUARE_ROUND, Math.PI / 4);
        const base = slabOf(triangle, h);
        const morph = slabOf(square, h);
        const geometry = new THREE.BufferGeometry();

        geometry.setIndex(SLAB_INDEX);
        geometry.setAttribute(
            'position',
            new THREE.BufferAttribute(base.position, 3),
        );
        geometry.setAttribute(
            'normal',
            new THREE.BufferAttribute(base.normal, 3),
        );
        geometry.morphAttributes.position = [
            new THREE.BufferAttribute(morph.position, 3),
        ];
        geometry.morphAttributes.normal = [
            new THREE.BufferAttribute(morph.normal, 3),
        ];

        // Three stops: dark at the core, lighter at the tips, and leaning
        // along the diagonal — the reference's gradient, not a flat wash.
        const a = new THREE.Color(inner);
        const b = new THREE.Color(mid);
        const d = new THREE.Color(outer);
        const c = new THREE.Color();
        const count = base.position.length / 3;
        const colours = new Float32Array(count * 3);

        for (let i = 0; i < count; i++) {
            const x = base.position[i * 3]!;
            const y = base.position[i * 3 + 1]!;
            const radial = clamp01(Math.hypot(x, y) / size);
            const diagonal = clamp01((x - y) / (size * 2.4) + 0.5);
            // Weighted to the diagonal and biased dark, so most of a plate
            // keeps its deep colour and the light only reaches one edge.
            const t = (0.25 * radial + 0.75 * diagonal) ** 1.35;

            c.copy(a)
                .lerp(b, clamp01(t * 2))
                .lerp(d, clamp01(t * 2 - 1));
            colours.set([c.r, c.g, c.b], i * 3);
        }

        geometry.setAttribute('color', new THREE.BufferAttribute(colours, 3));

        // Both outlines at both faces: enough to measure a form's spread.
        const silhouette = new Float64Array(Math.ceil(COLUMNS / STEP) * 4);
        let o = 0;

        for (let k = 0; k < COLUMNS; k += STEP) {
            silhouette[o] = triangle.p[k * 2]!;
            silhouette[o + 1] = triangle.p[k * 2 + 1]!;
            silhouette[o + 2] = square.p[k * 2]!;
            silhouette[o + 3] = square.p[k * 2 + 1]!;
            o += 4;
        }

        silhouettes.push(silhouette);

        // Matte: no clearcoat, nothing self-lit — only a soft sheen that
        // catches the rolled edge. The gradient does the work.
        const material = new THREE.MeshPhysicalMaterial({
            vertexColors: true,
            roughness: frosted ? 0.75 : 0.62,
            metalness: 0,
            sheen: 0.25,
            sheenColor: new THREE.Color('#C9BDFF'),
            sheenRoughness: 0.8,
            transparent: frosted,
            opacity: frosted ? 0.92 : 1,
            side: THREE.FrontSide,
        });

        const mesh = new THREE.Mesh(geometry, material);

        // Five meshes, each always near the camera: culling by a bounding
        // sphere that ignores the morph would only ever get it wrong.
        mesh.frustumCulled = false;

        return mesh;
    });

    object.add(...slabs);
    scene.add(object);

    const euler = new THREE.Euler(0, 0, 0, 'ZXY');
    const toQuat = (r: Vec): Quaternion =>
        new THREE.Quaternion().setFromEuler(
            euler.set(
                THREE.MathUtils.degToRad(r[0]),
                THREE.MathUtils.degToRad(r[1]),
                THREE.MathUtils.degToRad(r[2]),
                'ZXY',
            ),
        );

    const slabQuats = STATES.map(({ slabs: poses }) =>
        poses.map((pose) => toQuat(pose.r ?? [0, 0, 0])),
    );
    const turnQuats = STATES.map(({ turn }) => toQuat(turn));

    /** How wide each form reads, in the object's own units: its silhouette,
     *  posed and turned. Measured once — it never changes. */
    const EXTENT = STATES.map((state, s) => {
        const probe = new THREE.Vector3();
        const home = new THREE.Vector3();
        let minX = Infinity;
        let maxX = -Infinity;
        let minY = Infinity;
        let maxY = -Infinity;

        state.slabs.forEach((pose, index) => {
            const scale = pose.s ?? [1, 1, 1];
            const m = pose.m ?? 0;
            const h = PIECES[index]![0] * THICK;
            const silhouette = silhouettes[index]!;

            home.set(pose.p[0], pose.p[1], pose.p[2]);

            for (let o = 0; o < silhouette.length; o += 4) {
                const x = lerp(silhouette[o]!, silhouette[o + 2]!, m);
                const y = lerp(silhouette[o + 1]!, silhouette[o + 3]!, m);

                for (const z of [h, -h]) {
                    probe
                        .set(x * scale[0], y * scale[1], z * scale[2])
                        .applyQuaternion(slabQuats[s]![index]!)
                        .add(home)
                        .applyQuaternion(turnQuats[s]!);

                    minX = Math.min(minX, probe.x);
                    maxX = Math.max(maxX, probe.x);
                    minY = Math.min(minY, probe.y);
                    maxY = Math.max(maxY, probe.y);
                }
            }
        });

        return Math.max(maxX - minX, maxY - minY);
    });

    // ---- The field around it: a crosshair, a ring, dashed guides -----------

    // Held apart from the object: it is drawn to the composition's size on
    // the screen, not to whatever spread the current form happens to have.
    const decor: Group = new THREE.Group();

    scene.add(decor);

    const TINT = new THREE.Color('#6B5BD6');
    /** How far out a line keeps its full strength, and how far it falls. */
    const REACH = 0.3;
    const FALL = 0.85;

    /** A vertex alpha per point: full near the middle, almost gone at the
     *  left and right margins, so no line ends on a hard stop. */
    const faded = (points: Vector3[], reach: number): Float32Array => {
        const colour = new Float32Array(points.length * 4);

        points.forEach((point, i) => {
            const out = clamp01(
                (Math.abs(point.x) / reach - REACH) / (1 - REACH),
            );

            colour[i * 4] = TINT.r;
            colour[i * 4 + 1] = TINT.g;
            colour[i * 4 + 2] = TINT.b;
            colour[i * 4 + 3] = 1 - smooth(out) * FALL;
        });

        return colour;
    };

    /** A line through the given points, fading towards the margins. */
    const fadedLine = (points: Vector3[], reach: number) => {
        const geometry = new THREE.BufferGeometry().setFromPoints(points);

        geometry.setAttribute(
            'color',
            new THREE.BufferAttribute(faded(points, reach), 4),
        );

        return geometry;
    };

    const circle = (r: number) =>
        fadedLine(
            new THREE.EllipseCurve(0, 0, r, r)
                .getPoints(128)
                .map((v) => new THREE.Vector3(v.x, v.y, 0)),
            r,
        );

    /** The upright axis: on x = 0 throughout, so it fades along itself. */
    const upright = (bottom: number, top: number) => {
        const SEG = 32;
        const points = Array.from(
            { length: SEG + 1 },
            (_, i) => new THREE.Vector3(0, lerp(bottom, top, i / SEG), 0),
        );
        const geometry = new THREE.BufferGeometry().setFromPoints(points);
        const colour = new Float32Array((SEG + 1) * 4);

        for (let i = 0; i <= SEG; i++) {
            colour[i * 4] = TINT.r;
            colour[i * 4 + 1] = TINT.g;
            colour[i * 4 + 2] = TINT.b;
            colour[i * 4 + 3] = Math.sin((Math.PI * i) / SEG) ** 0.9;
        }

        geometry.setAttribute('color', new THREE.BufferAttribute(colour, 4));

        return geometry;
    };

    /** A hairline that fades out at both ends. */
    const hairline = (from: [number, number], to: [number, number]): Line => {
        const SEG = 48;
        const position = new Float32Array((SEG + 1) * 3);
        const colour = new Float32Array((SEG + 1) * 4);

        for (let i = 0; i <= SEG; i++) {
            const t = i / SEG;

            position[i * 3] = lerp(from[0], to[0], t);
            position[i * 3 + 1] = lerp(from[1], to[1], t);
            colour[i * 4] = TINT.r;
            colour[i * 4 + 1] = TINT.g;
            colour[i * 4 + 2] = TINT.b;
            colour[i * 4 + 3] = Math.sin(Math.PI * t) ** 1.6;
        }

        const geometry = new THREE.BufferGeometry();

        geometry.setAttribute(
            'position',
            new THREE.BufferAttribute(position, 3),
        );
        geometry.setAttribute('color', new THREE.BufferAttribute(colour, 4));

        return new THREE.Line(
            geometry,
            new THREE.LineBasicMaterial({
                vertexColors: true,
                transparent: true,
                depthWrite: false,
                opacity: 0,
            }),
        );
    };

    const cross = new THREE.Group();
    const crossLines = [
        hairline([-1.15, -0.06], [1.15, -0.06]),
        hairline([0.14, -0.98], [0.14, 0.98]),
    ];

    cross.add(...crossLines);

    // The ring, with three points going round it.
    const ring = new THREE.Group();
    const ringLine = new THREE.LineLoop(
        circle(0.58),
        new THREE.LineBasicMaterial({
            vertexColors: true,
            transparent: true,
            opacity: 0,
            depthWrite: false,
        }),
    );
    const dotMaterial = new THREE.MeshBasicMaterial({
        color: 0x775afc,
        transparent: true,
        opacity: 0,
    });
    const dots = [0, 1, 2].map(
        () =>
            new THREE.Mesh(new THREE.SphereGeometry(0.014, 12, 8), dotMaterial),
    );

    ring.add(ringLine, ...dots);

    // 01's blueprint: dashed guides.
    const dashed = () =>
        new THREE.LineDashedMaterial({
            vertexColors: true,
            dashSize: 0.024,
            gapSize: 0.02,
            transparent: true,
            opacity: 0,
            depthWrite: false,
        });
    const guides = new THREE.Group();
    // Two rings on the ground it is built on, and the axis it goes up.
    const guideLines = [
        new THREE.LineLoop(circle(0.62), dashed()),
        new THREE.LineLoop(circle(0.45), dashed()),
        new THREE.Line(upright(-0.52, 0.74), dashed()),
    ];

    guideLines[0]!.rotation.set(THREE.MathUtils.degToRad(78), 0, 0.1);
    guideLines[0]!.position.y = -0.33;
    guideLines[1]!.rotation.set(THREE.MathUtils.degToRad(78), 0, 0.1);
    guideLines[1]!.position.y = -0.33;
    guideLines.forEach((line) => line.computeLineDistances());
    guides.add(...guideLines);

    decor.add(cross, ring, guides);

    // The haze under it: a soft violet ellipse that breathes with the float.
    const hazeCanvas = document.createElement('canvas');

    hazeCanvas.width = hazeCanvas.height = 128;

    const hazeContext = hazeCanvas.getContext('2d');

    if (hazeContext) {
        const fill = hazeContext.createRadialGradient(64, 64, 0, 64, 64, 64);

        fill.addColorStop(0, 'rgba(136, 104, 255, 1)');
        fill.addColorStop(1, 'rgba(136, 104, 255, 0)');
        hazeContext.fillStyle = fill;
        hazeContext.fillRect(0, 0, 128, 128);
    }

    const hazeTexture = new THREE.CanvasTexture(hazeCanvas);

    hazeTexture.colorSpace = THREE.SRGBColorSpace;

    const hazeMaterial = new THREE.MeshBasicMaterial({
        map: hazeTexture,
        transparent: true,
        opacity: 0.2,
        depthWrite: false,
    });
    const haze = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), hazeMaterial);

    haze.renderOrder = -1;
    scene.add(haze);

    // ---- The scroll --------------------------------------------------------

    const narrow = matchMedia('(max-width: 1023px)');
    const phone = matchMedia('(max-width: 767px)');

    /**
     * Where it is on the path, scrubbed: `enter` as the stage comes in, then
     * `s1`–`s4` (S0 to S1 alongside that entry, so a chapter is never read
     * against a half-finished form; to S2 and S3 as chapters 02 and 03 rise
     * from 80% to 15%; to S4 as the panel's slot comes to the middle). Each
     * its own value: two scrubbed tweens on one value would fight.
     */
    const path = { enter: 0, s1: 0, s2: 0, s3: 0, s4: 0 };
    const scrub = (
        key: keyof typeof path,
        trigger: Element,
        start: string,
        end: string,
    ): void => {
        gsap.fromTo(
            path,
            { [key]: 0 },
            {
                [key]: 1,
                ease: 'none',
                scrollTrigger: { trigger, start, end, scrub: 1 },
            },
        );
    };

    if (!reduced) {
        scrub('enter', stage, 'top 95%', 'top 55%');
        // Settled into 01's form while it is still coming in, below the fold:
        // by the time the chapter can be read it has one form, not two.
        scrub('s1', stage, 'top 92%', 'top 60%');
        scrub('s2', chapters[1]!.el, 'top 80%', 'top 15%');
        scrub('s3', chapters[2]!.el, 'top 80%', 'top 15%');
        scrub('s4', panelSlot, 'top 88%', 'center 36%');
    }

    /** Phones and tablets with reduced motion: the box that came in last. */
    const inBoxes = (vh: number): { index: number; phase: number } => {
        let index = 0;

        slots.forEach((slot, i) => {
            if (slot.getBoundingClientRect().top < vh) {
                index = i;
            }
        });

        const top = slots[index]!.getBoundingClientRect().top;

        return { index, phase: index + ramp(top, vh, vh * 0.55) };
    };

    /**
     * Phones and tablets, with motion: the box it is in or flying to, and
     * how far along (0 in the box before, 1 landed). A flight runs while the
     * next box's top rises from FLIGHT.from to FLIGHT.to screens; the first
     * box has none, the object comes up with it.
     */
    const inFlight = (vh: number): { index: number; k: number } => {
        let index = 0;

        slots.forEach((slot, i) => {
            if (i > 0 && slot.getBoundingClientRect().top < vh * FLIGHT.from) {
                index = i;
            }
        });

        const top = slots[index]!.getBoundingClientRect().top;

        return index === 0
            ? { index, k: ramp(top, vh, vh * FLIGHT.to) }
            : { index, k: ramp(top, vh * FLIGHT.from, vh * FLIGHT.to) };
    };

    /** Its size in a box. A chapter's box is taller than it is wide, so its
     *  height sets the size; the panel's slot is the other way about, and the
     *  W is a broad form, so there the width does. */
    const boxSize = (index: number, rect: DOMRect): number =>
        index === slots.length - 1
            ? Math.min(rect.width * PANEL_FILL_NARROW, rect.height * PANEL_CAP)
            : rect.height * (phone.matches ? PHONE_FILL : BOX_FILL);

    /** Reduced motion: the form it shows, and its crossfade. */
    const still = { state: 1, fade: 1 };
    const stillTarget = (vh: number): number =>
        narrow.matches
            ? inBoxes(vh).index + 1
            : 1 +
              [chapters[1]!.el, chapters[2]!.el, panelSlot].filter(
                  (el) => el.getBoundingClientRect().top < vh / 2,
              ).length;

    // ---- When to draw ------------------------------------------------------

    let inView = false;

    new IntersectionObserver(
        ([entry]) => {
            inView = entry?.isIntersecting ?? false;

            if (!inView) {
                renderer.domElement.style.visibility = 'hidden';
            }
        },
        { rootMargin: '25% 0px' },
    ).observe(stage);

    // ---- Each frame --------------------------------------------------------

    const content = document.getElementById('smooth-content');
    const tmpQ: Quaternion = new THREE.Quaternion();
    const extra: Quaternion = new THREE.Quaternion();
    const tilt = new THREE.Euler();
    let width = 0;
    let height = 0;

    const resize = (): void => {
        // The host's own height (100vh), not innerHeight: on phones that
        // changes as the address bar comes and goes, which would stretch the
        // drawing over the host and move the object with it.
        const w = window.innerWidth;
        const h = host.clientHeight || window.innerHeight;

        if (w === width && h === height) {
            return;
        }

        width = w;
        height = h;
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.75));
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
    };

    const frame = (time: number): void => {
        if (!inView || document.hidden) {
            return;
        }

        // Held to the screen inside the smooth-scrolled content.
        if (getSmoother() && content) {
            const y = Number(gsap.getProperty(content, 'y')) || 0;

            host.style.transform = `translate3d(0, ${-y}px, 0)`;
        }

        const vh = height;
        const box = stage.getBoundingClientRect();
        let phase: number;
        let opacity = 1;

        if (reduced) {
            const target = stillTarget(vh);

            if (target !== still.state && !gsap.isTweening(still)) {
                gsap.timeline()
                    .to(still, { fade: 0, duration: 0.2, ease: 'none' })
                    .set(still, { state: target })
                    .to(still, { fade: 1, duration: 0.2, ease: 'none' });
            }

            phase = still.state;
            opacity = still.fade;
        } else if (narrow.matches) {
            const { index, k } = inFlight(vh);

            phase = index + k;
        } else {
            phase = path.s1 + path.s2 + path.s3 + path.s4;
            opacity = path.enter;
        }

        let x: number;
        let y: number;
        let size: number;

        if (narrow.matches && reduced) {
            const index = inBoxes(vh).index;
            const rect = slots[index]!.getBoundingClientRect();

            x = rect.left + rect.width / 2;
            y = rect.top + rect.height / 2;
            size = boxSize(index, rect);
        } else if (narrow.matches) {
            const { index, k } = inFlight(vh);
            const b = slots[index]!.getBoundingClientRect();
            const bx = b.left + b.width / 2;
            const by = b.top + b.height / 2;
            const bs = boxSize(index, b);

            if (index === 0 || k === 0) {
                // In its box — or, before a flight, still in the one before.
                const a =
                    slots[Math.max(index - 1, 0)]!.getBoundingClientRect();
                const at = index === 0 ? b : a;

                x = at.left + at.width / 2;
                y = at.top + at.height / 2;
                size = index === 0 ? bs : boxSize(index - 1, a);
            } else {
                const a = slots[index - 1]!.getBoundingClientRect();
                const as = boxSize(index - 1, a);
                const e = smooth(k);
                const arc = Math.sin(Math.PI * e);
                // From its box, or from just above the screen's top if that
                // has scrolled away.
                const fromY = Math.max(a.top + a.height / 2, -as * 0.6);
                // Out to one side and back, alternating flight to flight.
                const side = index % 2 === 1 ? -1 : 1;

                x =
                    lerp(a.left + a.width / 2, bx, e) +
                    side * arc * width * FLIGHT.swing;
                y = lerp(fromY, by, e);
                size = lerp(as, bs, e) * (1 - FLIGHT.shrink * arc);
            }
        } else {
            // In the middle of the screen, in the half of the chapter it is
            // in (or crossing between two), until the panel takes it: there
            // it sits in the panel's slot and rides it off the screen.
            const along = Math.min(Math.max(phase - 1, 0), 3);
            const i = Math.min(Math.floor(along), 2);
            const a = slots[i]!.getBoundingClientRect();
            const b = slots[i + 1]!.getBoundingClientRect();
            const k = smooth(along - i);
            const home = panelSlot.getBoundingClientRect();
            const settled = smooth(clamp01(along - 2));
            // A quarter of the screen below the stage's top while it comes
            // in — the pinwheel is in view while that lasts — and then the
            // middle, which is where each chapter centres its text too. It
            // is not pulled up as the last chapter ends: `settled` already
            // hands it over to the panel, and doing both left it drifting
            // above the text it belongs to.
            const hold = Math.max(vh / 2, box.top + vh / 4);
            const inHalf = Math.min(a.width * CHAPTER_FILL, vh * CHAPTER_CAP);
            const onPanel = Math.min(
                home.width * PANEL_FILL,
                home.height * PANEL_CAP,
            );

            x = lerp(a.left + a.width / 2, b.left + b.width / 2, k);
            y = lerp(hold, home.top + home.height / 2, settled);
            size = lerp(inHalf, onPanel, settled);
        }

        const visible = opacity > 0.001 && y > -size && y < vh + size;

        renderer.domElement.style.visibility = visible ? '' : 'hidden';
        renderer.domElement.style.opacity = opacity.toFixed(3);

        if (!visible) {
            return;
        }

        // The forms either side, and how far between (eased per step).
        const from = Math.min(Math.floor(phase), STATES.length - 1);
        const to = Math.min(from + 1, STATES.length - 1);
        const t = smooth(phase - from);
        const sa = STATES[from]!;
        const sb = STATES[to]!;
        const finale = near(phase, 4);
        const float = reduced ? 0 : Math.sin((time * Math.PI * 2) / 6);

        slabs.forEach((mesh, index) => {
            const pa = sa.slabs[index]!;
            const pb = sb.slabs[index]!;
            const ka = pa.s ?? [1, 1, 1];
            const kb = pb.s ?? [1, 1, 1];
            // On the panel each one bobs on its own, a beat after the last.
            const bob = reduced
                ? 0
                : Math.sin((time * Math.PI * 2) / 5 + index * 0.9) *
                  0.05 *
                  finale;

            mesh.position.set(
                lerp(pa.p[0], pb.p[0], t),
                lerp(pa.p[1], pb.p[1], t) + bob,
                lerp(pa.p[2], pb.p[2], t),
            );
            mesh.quaternion.slerpQuaternions(
                slabQuats[from]![index]!,
                slabQuats[to]![index]!,
                t,
            );
            mesh.scale.set(
                lerp(ka[0], kb[0], t),
                lerp(ka[1], kb[1], t),
                lerp(ka[2], kb[2], t),
            );

            const influences = mesh.morphTargetInfluences;

            if (influences) {
                influences[0] = lerp(pa.m ?? 0, pb.m ?? 0, t);
            }
        });

        // How much of each chapter it is: the field it stands in.
        const blueprint = near(phase, 1);
        const orbiting = near(phase, 2) + near(phase, 3);
        const chapterness = blueprint + orbiting;

        crossLines.forEach((line) => {
            (line.material as { opacity: number }).opacity = chapterness * 0.22;
        });
        cross.visible = chapterness > 0.01;
        guideLines.forEach((line, n) => {
            (line.material as { opacity: number }).opacity =
                blueprint * (n === 2 ? 0.2 : 0.3);
        });
        guides.visible = blueprint > 0.01;
        (ringLine.material as { opacity: number }).opacity = orbiting * 0.2;
        dotMaterial.opacity = orbiting * 0.7;
        ring.visible = orbiting > 0.01;
        ring.rotation.set(
            THREE.MathUtils.degToRad(lerp(RING[from]![0], RING[to]![0], t)),
            0,
            THREE.MathUtils.degToRad(lerp(RING[from]![1], RING[to]![1], t)),
        );
        dots.forEach((dot, n) => {
            const angle =
                (reduced ? 0 : (time * Math.PI * 2) / 16) + (n * TAU) / 3;

            dot.position.set(Math.cos(angle) * 0.58, Math.sin(angle) * 0.58, 0);
        });
        // The whole object: from px on the screen to the camera's world.
        const worldH =
            2 *
            Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) *
            camera.position.z;
        const worldW = worldH * camera.aspect;
        // Every form drawn to the same width: its own spread divides out.
        const target = (size / vh) * worldH;
        const scale = target / lerp(EXTENT[from]!, EXTENT[to]!, t);
        const centreY = (0.5 - y / vh) * worldH;
        // ±4° with the scroll through each chapter.
        const read = clamp01((vh / 2 - box.top) / box.height) * 3;
        const sway = reduced ? 0 : Math.sin(Math.PI * read);

        object.position.set(
            (x / width - 0.5) * worldW,
            centreY + float * 0.02 * target,
            0,
        );
        object.scale.setScalar(scale);
        object.quaternion.slerpQuaternions(
            turnQuats[from]!,
            tmpQ.copy(turnQuats[to]!),
            t,
        );
        object.quaternion.premultiply(
            extra.setFromEuler(
                tilt.set(
                    THREE.MathUtils.degToRad(-2 * sway + float),
                    THREE.MathUtils.degToRad(4 * sway),
                    0,
                ),
            ),
        );

        decor.position.copy(object.position);
        decor.scale.setScalar(target);

        // The haze: under it, wider as it sinks, narrower as it rises, and
        // heavier on the panel, where it is the only light behind the W.
        // In the chapters it sits under the object; on the panel it rises
        // behind the W, where it is the only light the plates stand against.
        haze.position.set(
            object.position.x,
            object.position.y - lerp(0.56, 0.08, finale) * target,
            -4,
        );
        haze.scale.set(
            lerp(2.7, 1.9, finale) * target * (1 - 0.05 * float),
            lerp(0.48, 1.05, finale) * target,
            1,
        );
        hazeMaterial.opacity = (0.2 + 0.1 * finale) * (1 - 0.15 * float);

        renderer.render(scene, camera);
    };

    resize();
    window.addEventListener('resize', resize);
    gsap.ticker.add(frame);
}

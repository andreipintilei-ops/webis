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
import {
    BRAND,
    clamp01,
    COLUMNS,
    hasWebGL,
    lerp,
    outlineOf,
    PIECES,
    SLAB_INDEX,
    slabOf,
    SQUARE_ROUND,
    TAU,
    THICK,
    TRI_ROUND,
    W,
} from '@/public/plates';
import type { Pose, Vec } from '@/public/plates';
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
 *   rounded squares in the logo's colours (S4), and ride it off the screen.
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
 * Below, it sits behind the text: a little above the screen's middle, dimmed so
 * the chapters read over it, taking its forms with the same scroll as
 * above; it comes up to full strength as it settles into the panel's W.
 *
 * The screen's height is the fixed layer's own (100vh, the largest
 * viewport), never innerHeight: on phones the address bar coming and going
 * changes the window's height, and the object would jump with it.
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

interface State {
    slabs: Pose[];
    /** The whole object's turn, in degrees. */
    turn: Vec;
}

const smooth = (t: number): number => t * t * (3 - 2 * t);
/** 1 at form `k`, 0 a form away. */
const near = (q: number, k: number): number =>
    smooth(clamp01(1 - Math.abs(q - k)));

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
/**
 * Phones and tablets: behind the text, its form fills this much of the
 * screen's width, capped against its height. It opens the stage alone, at
 * full strength (the first chapter leaves it the room,
 * css/site/variant-stage.css); as the text comes up over it, it eases to
 * this opacity and softens behind a blur of this many pixels — glass in
 * front of it, the plates still told apart — until it settles into the
 * panel, sharp again.
 */
const BEHIND_FILL = 0.85;
const BEHIND_CAP = 0.45;
const BEHIND_DIM = 0.85;
const BEHIND_BLUR = 3;
/** The even light that stands for the room's, in the chapters and on the
 *  panel (no environment map: see the scene). */
const AMBIENT: [number, number] = [3.9, 2.1];
/** Phones and tablets: where it is held, down the screen. Above the middle,
 *  so it rides up with the page for longer and the screen's top is not left
 *  empty while the text has still to come up. */
const BEHIND_HOLD = 0.36;
/**
 * The pixel ratio it is drawn at while mostly blurred: the blur hides the
 * lost detail, and the fewer pixels pay for the blur. Taken past the first
 * share of the veil, given back below the second, so it does not flicker
 * between the two at one point.
 */
const BEHIND_PIXEL_RATIO = 1.25;
const BEHIND_LOW_RES_IN = 0.6;
const BEHIND_LOW_RES_OUT = 0.35;

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

    /** The first chapter's text: on phones, what comes up over the object. */
    const firstText =
        chapters[0]!.el.querySelector<HTMLElement>('.stage-chapter__body') ??
        chapters[0]!.el;

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

    // Drawn live: below 1024px the chapters' boxes are not needed any more
    // (css/site/variant-stage.css), the object sits behind the text.
    stage.classList.add('stage--live');

    const [THREE, gsap, { ScrollTrigger }] = await Promise.all([
        import('three'),
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

    // No environment map: the room three.js would build for it
    // (RoomEnvironment through PMREMGenerator) compiles shaders of its own
    // that cannot be compiled in the background — a second with the page
    // held up. Its soft light from every side is an even light instead
    // (AMBIENT), eased down with the rest as the plates settle (paint).
    const scene: Scene = new THREE.Scene();

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

    const ambient = new THREE.AmbientLight(0xffffff, AMBIENT[0]);

    key.position.set(-5, 7, 8);
    rim.position.set(6, -3, -7);
    scene.add(hemi, ambient, key, rim);

    // ---- The slabs ---------------------------------------------------------

    const object = new THREE.Group();
    /** Per slab, a ring of points on each outline: what each form is measured
     *  by, so every form is drawn to the same width. */
    const silhouettes: Float64Array[] = [];
    const STEP = 8;

    /** Each plate's own gradient, kept to blend from into its brand colour. */
    const gradients: Float32Array[] = [];
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

        geometry.setAttribute(
            'color',
            new THREE.BufferAttribute(colours.slice(), 3),
        );
        gradients.push(colours);

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

    // On the panel, the plates come into the logo's colours: each gradient
    // blends to its flat colour, lit a little from within so it reads as
    // flat as the logo; the frosted one turns solid, the violet sheen goes.
    const brand = BRAND.map((hex) => new THREE.Color(hex));
    let painted = -1;

    const paint = (amount: number): void => {
        // Eased, and only when it has changed enough to see.
        const k = amount * amount * (3 - 2 * amount);

        if (Math.abs(k - painted) < 0.002) {
            return;
        }

        painted = k;

        // Softer light, and less of the violet rim: bright light is what the
        // tone mapping turns pastel.
        hemi.intensity = lerp(1.05, 0.55, k);
        key.intensity = lerp(1.45, 0.95, k);
        rim.intensity = lerp(1.5, 0.3, k);
        ambient.intensity = lerp(AMBIENT[0], AMBIENT[1], k);

        slabs.forEach((mesh, index) => {
            const from = gradients[index]!;
            const to = brand[index]!;
            const attribute = mesh.geometry.getAttribute(
                'color',
            ) as InstanceType<typeof THREE.BufferAttribute>;
            const colours = attribute.array as Float32Array;

            for (let i = 0; i < from.length; i += 3) {
                colours[i] = lerp(from[i]!, to.r, k);
                colours[i + 1] = lerp(from[i + 1]!, to.g, k);
                colours[i + 2] = lerp(from[i + 2]!, to.b, k);
            }

            attribute.needsUpdate = true;

            const material = mesh.material as InstanceType<
                typeof THREE.MeshPhysicalMaterial
            >;
            const frosted = PIECES[index]![4];

            material.emissive.copy(to);
            material.emissiveIntensity = 0.2 * k;
            material.sheen = 0.25 * (1 - k);
            // No white highlight washing the colour out.
            material.specularIntensity = 1 - 0.9 * k;
            material.roughness = lerp(frosted ? 0.75 : 0.62, 0.95, k);
            material.opacity = frosted ? lerp(0.92, 1, k) : 1;
        });
    };

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
        scrub('s4', panelSlot, 'top 88%', 'center 48%');
    }

    // `stage--live` changed the layout below 1024px (the boxes gone, the
    // room the object opens in) after the page's other scroll triggers —
    // the reveals, the panel's growth — were measured: measured again, or
    // they fire hundreds of pixels late.
    ScrollTrigger.refresh();

    /** Reduced motion: the form it shows, and its crossfade. */
    const still = { state: 1, fade: 1 };
    const stillTarget = (vh: number): number =>
        1 +
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
        // Laid in the page for the old size: placed again on the next frame.
        anchor(null);
        riding = null;
        renderer.setPixelRatio(pixelRatio());
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
    };

    /** Phones: drawn at fewer pixels while blurred behind the text. */
    let lowRes = false;
    const pixelRatio = (): number =>
        Math.min(window.devicePixelRatio, lowRes ? BEHIND_PIXEL_RATIO : 1.75);
    const setLowRes = (on: boolean): void => {
        if (on === lowRes) {
            return;
        }

        lowRes = on;
        renderer.setPixelRatio(pixelRatio());
        renderer.setSize(width, height, false);
    };
    let blurred = '';

    /**
     * Wherever the object moves with the page, the layer rides the page
     * instead of being held to the screen: where the browser scrolls on its
     * own (phones, with no smoothing), the object redrawn after each scroll
     * would trail the page by a frame — it shook. Laid in the page at a
     * given top on the screen (`top`), the layer moves with the page and
     * the object stays put inside it; null holds it to the screen again.
     */
    let anchored = false;
    const anchor = (top: number | null): void => {
        anchored = top !== null;

        if (top === null) {
            host.style.position = '';
            host.style.top = '';

            return;
        }

        host.style.position = 'absolute';
        host.style.top = '0px';
        host.style.top = `${top - host.getBoundingClientRect().top}px`;
    };
    /** Phones: where it rides the page — in with the stage, out with the panel. */
    let riding: 'in' | 'out' | null = null;

    /** Held to the screen inside the smooth-scrolled content. */
    const holdToScreen = (): void => {
        if (getSmoother() && content && !anchored) {
            const y = Number(gsap.getProperty(content, 'y')) || 0;

            host.style.transform = `translate3d(0, ${-y}px, 0)`;
        }
    };

    const frame = (time: number): void => {
        if (!inView || document.hidden) {
            return;
        }

        holdToScreen();

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
        } else {
            phase = path.s1 + path.s2 + path.s3 + path.s4;
            opacity = path.enter;
        }

        let x: number;
        let y: number;
        let size: number;
        // Behind the text (phones): how far, 0–1, from alone to under it.
        let veil = 0;

        if (narrow.matches) {
            // Alone at first, a quarter of the screen below the stage's top
            // and then held above the middle (BEHIND_HOLD); behind the text
            // once it comes up over it, a little dimmed and blurred, until
            // the panel takes it: there it stands sharp, at full strength, in
            // the panel's slot and rides it off the screen. While held it is
            // fixed to the screen; before and after, the layer rides the page (`anchor`),
            // the stage's top or the slot's middle carrying it — never
            // redrawn after the scroll, so it cannot trail it.
            const along = Math.min(Math.max(phase - 1, 0), 3);
            const home = panelSlot.getBoundingClientRect();
            const settled = smooth(clamp01(along - 2));
            const held = vh * BEHIND_HOLD;
            const hold = Math.max(held, box.top + vh / 4);
            const slotMiddle = home.top + home.height / 2;
            const behind = Math.min(width * BEHIND_FILL, vh * BEHIND_CAP);
            const onPanel = Math.min(
                home.width * PANEL_FILL_NARROW,
                home.height * PANEL_CAP,
            );
            const ride =
                box.top + vh / 4 > held
                    ? 'in'
                    : slotMiddle < held
                      ? 'out'
                      : null;

            x = lerp(width / 2, home.left + home.width / 2, settled);
            y = ride === 'in' ? hold : ride === 'out' ? slotMiddle : held;
            size = lerp(behind, onPanel, settled);

            // Laid in the page with the object where it is held on the
            // layer, at its place on the screen now; held to the screen again
            // where the two meet, so the hand-over does not move it.
            if (ride !== riding) {
                riding = ride;
                anchor(ride ? y - held : null);
                holdToScreen();
            }

            // From the text's top reaching the object's lower edge to its
            // middle.
            const textTop = firstText.getBoundingClientRect().top;
            const under = smooth(
                clamp01((hold + behind / 2 - textTop) / (behind / 2)),
            );

            veil = under * (1 - settled);
            opacity *= lerp(1, BEHIND_DIM, veil);
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

        // From 1024px: standing on the panel from the moment the path ends
        // (the scrub has caught up). Either way, drawn where it is on the
        // layer, not the screen.
        if (!narrow.matches && phase >= STATES.length - 1 !== anchored) {
            anchor(anchored ? null : host.getBoundingClientRect().top);
        }

        if (anchored) {
            y -= host.getBoundingClientRect().top;
        }

        const visible = opacity > 0.001 && y > -size && y < vh + size;

        renderer.domElement.style.visibility = visible ? '' : 'hidden';
        renderer.domElement.style.opacity = opacity.toFixed(3);

        // The glass: the blur in tenths of a pixel, set only when it
        // changes; none at all once it is sharp, so nothing is filtered.
        const blur = Math.round(veil * BEHIND_BLUR * 10) / 10;
        const filter = blur > 0 ? `blur(${blur}px)` : '';

        if (filter !== blurred) {
            blurred = filter;
            renderer.domElement.style.filter = filter;
        }

        setLowRes(
            lowRes ? veil > BEHIND_LOW_RES_OUT : veil > BEHIND_LOW_RES_IN,
        );

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

        paint(clamp01((finale - 0.55) / 0.45));

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

    // Every shader compiled before the first frame — in the background where
    // the browser can (KHR_parallel_shader_compile). The parts hidden until
    // their chapter (the cross, the guides, the ring and its dots) were
    // otherwise compiled the moment they first showed, mid-scroll: over a
    // second of stalls on a phone, as the panel and the clients' wall came
    // up. Shown for it only: each frame sets them again.
    for (const part of [cross, guides, ring]) {
        part.visible = true;
    }

    await renderer.compileAsync(scene, camera).catch(() => undefined);

    gsap.ticker.add(frame);
}

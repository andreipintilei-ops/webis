import type {
    BufferAttribute,
    Material,
    Mesh,
    MeshPhysicalMaterial,
    PerspectiveCamera,
    Quaternion,
    Vector3,
    WebGLRenderer,
} from 'three';
import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The services story (trial on /clienti; components/site/services-story,
 * css/site/variant-clienti.css): one pinned section, one master timeline
 * scrubbed by the scroll, one Webis object — four soft triangular fins,
 * gently bent, crossing at a shared centre — taking a state per chapter:
 *
 * - intro: the pinwheel, with an orbit around it naming the three chapters;
 * - 01 Software la comandă: open and architectural — a front wall, a rear
 *   wall, a base, a diagonal brace — with guide lines and callouts;
 * - 02 Produse SaaS: a shell — the four fins as the faces of a tetrahedron —
 *   its front face swung aside to show the product inside; the ground turns
 *   indigo, a slow pulse and a few orbiting points say it is running;
 * - 03 Web și e-commerce: flat and wide, the fins facing out, a site and a
 *   shop in front of them;
 * - exit: back to the pinwheel, smaller, fading before the reviews.
 *
 * Each state holds still for a while (the reading plateaus); the fins move
 * only between them. Besides the scroll: a slow breathing, and at most
 * 2.5° towards the pointer, only while a state holds. No spin.
 *
 * The section's text, ground and nav tone are tweened by the same timeline
 * (GSAP, in the DOM); the object is drawn from where the timeline is. With
 * reduced motion the object doesn't travel or breathe: it cross-fades from
 * state to state (400 ms). Without WebGL the text and ground still play.
 * Without this script the chapters are plain content, one after another.
 */

type Vec = [number, number, number];

interface FinState {
    p: Vec;
    /** Degrees, applied as: twist about the fin's own axis (Y), tilt (X),
     *  then spin in the screen's plane (Z). */
    r?: Vec;
    /** Or a turn worked out here (the shell). */
    q?: Quaternion;
    s?: Vec;
}

interface State {
    fins: FinState[];
    /** The whole object's turn (degrees), scale, sideways shift (of the
     *  width) and opacity. */
    rot: Vec;
    scale: number;
    shift: number;
    opacity: number;
}

/** The fins: size (to a corner), colour, the colour it leans to, how far. */
const FINS: [number, string, string, number][] = [
    [1.15, '#262A6F', '#775AFC', 0.85],
    [1.05, '#4B3FD1', '#A44CEE', 0.5],
    [0.95, '#F5F3FF', '#A18EFF', 0.55],
    [1, '#A18EFF', '#F5F3FF', 0.7],
];

/** The pinwheel: each fin pointing out, twisted about its own axis. */
const PINWHEEL: FinState[] = [
    { p: [0, -0.28, 0.22], r: [10, 48, 185] },
    { p: [0.2, 0.28, -0.05], r: [-8, -42, -35] },
    { p: [-0.32, 0.04, 0.02], r: [6, 52, 95] },
    { p: [0.28, -0.14, -0.22], r: [-6, -50, -118] },
];

const BASE = 2.6;
const DEPTH = 0.05;
const BEVEL = 0.03;
/** How much each fin is bent (z = BEND · x²). */
const BEND = 0.12;

const clamp01 = (v: number): number => Math.min(1, Math.max(0, v));
const smooth = (t: number): number => t * t * (3 - 2 * t);
const lerp = (a: number, b: number, t: number): number => a + (b - a) * t;
/** 1 at state `k`, 0 a state away (`width` narrows it to the plateau). */
const near = (q: number, k: number, width = 1): number =>
    smooth(clamp01(1 - Math.abs(q - k) * width));

export async function initServicesStory(): Promise<void> {
    const section = document.querySelector<HTMLElement>('[data-story]');
    const host = section?.querySelector<HTMLElement>('[data-story-canvas]');
    const panels = Array.from(
        section?.querySelectorAll<HTMLElement>('[data-story-panel]') ?? [],
    );

    if (!section || !host || panels.length !== 3) {
        return;
    }

    const reduced = prefersReducedMotion();
    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);
    section.classList.add('is-story');

    // ---- The master timeline: object state, text, ground ---------------------

    /** 0 intro, 1–3 the chapters, 4 exit. */
    const state = { p: 0 };
    const look = { dark: 0 };
    const paint = (): void => {
        section.style.setProperty('--story-dark', look.dark.toFixed(3));

        if (look.dark > 0.5) {
            section.dataset.navTone = 'dark';
        } else {
            delete section.dataset.navTone;
        }
    };

    gsap.set(panels.slice(1), { autoAlpha: 0, y: 28 });
    paint();

    const tl = gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: {
            trigger: section,
            start: 'top top',
            end: '+=450%',
            scrub: reduced ? true : 1,
            pin: true,
            anticipatePin: 1,
            invalidateOnRefresh: true,
        },
    });
    const move = { ease: 'power2.inOut', duration: 1 };
    const swap = (from: HTMLElement, to: HTMLElement, at: string): void => {
        tl.to(from, { autoAlpha: 0, y: -28, duration: 0.3 }, `${at}+=0.25`);
        tl.to(to, { autoAlpha: 1, y: 0, duration: 0.35 }, `${at}+=0.55`);
    };

    tl.addLabel('intro')
        .to({}, { duration: 0.4 })
        .to(state, { p: 1, ...move })
        .addLabel('software')
        .to({}, { duration: 0.8 })
        .addLabel('to-saas')
        .to(state, { p: 2, ...move }, 'to-saas')
        .to(look, { dark: 1, duration: 0.7, onUpdate: paint }, 'to-saas+=0.15')
        .addLabel('saas', 'to-saas+=1')
        .to({}, { duration: 0.8 }, 'saas')
        .addLabel('to-web')
        .to(state, { p: 3, ...move }, 'to-web')
        .to(look, { dark: 0, duration: 0.7, onUpdate: paint }, 'to-web+=0.15')
        .addLabel('web', 'to-web+=1')
        .to({}, { duration: 0.8 }, 'web')
        .addLabel('exit')
        .to(state, { p: 4, duration: 0.8, ease: 'power2.in' }, 'exit');

    swap(panels[0]!, panels[1]!, 'to-saas');
    swap(panels[1]!, panels[2]!, 'to-web');

    // ---- The object -----------------------------------------------------------

    const THREE = await import('three');
    let renderer: WebGLRenderer;

    try {
        renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'high-performance',
        });
    } catch {
        return;
    }

    const { RoomEnvironment } =
        await import('three/addons/environments/RoomEnvironment.js');

    renderer.setClearColor(0x000000, 0);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1;
    host.append(renderer.domElement);

    const scene = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);

    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;

    // A long lens: graphic, not a 3D demo.
    const camera: PerspectiveCamera = new THREE.PerspectiveCamera(
        18,
        1,
        0.1,
        100,
    );

    camera.position.set(0, 0, 30);

    const hemi = new THREE.HemisphereLight(0xf5f3ff, 0x1f2868, 1.1);
    const key = new THREE.DirectionalLight(0xffffff, 1.4);
    const rim = new THREE.DirectionalLight(0xa44cee, 1.2);
    // Inside the shell (02): its glow.
    const core = new THREE.PointLight(0x775afc, 0, 0, 1.6);

    key.position.set(-5, 7, 8);
    rim.position.set(6, -3, -7);
    scene.add(hemi, key, rim, core);

    // ---- The fins -------------------------------------------------------------------

    /** A triangle with very soft corners, its tip up, `size` to a corner. */
    const softTriangle = (size: number, r: number) => {
        const points = [0, 1, 2].map((i) => {
            const a = Math.PI / 2 + (i * 2 * Math.PI) / 3;

            return new THREE.Vector2(Math.cos(a) * size, Math.sin(a) * size);
        });
        const shape = new THREE.Shape();

        points.forEach((p, i) => {
            const prev = points[(i + 2) % 3]!;
            const next = points[(i + 1) % 3]!;
            const a = p.clone().add(prev.clone().sub(p).setLength(r));
            const b = p.clone().add(next.clone().sub(p).setLength(r));

            if (i === 0) {
                shape.moveTo(a.x, a.y);
            } else {
                shape.lineTo(a.x, a.y);
            }

            shape.quadraticCurveTo(p.x, p.y, b.x, b.y);
        });
        shape.closePath();

        return shape;
    };

    const object = new THREE.Group();
    const materials: MeshPhysicalMaterial[] = [];
    const fins: Mesh[] = FINS.map(([size, from, to, lean]) => {
        const geometry = new THREE.ExtrudeGeometry(
            softTriangle(size, size * 0.42),
            {
                depth: DEPTH,
                bevelEnabled: true,
                bevelThickness: BEVEL,
                bevelSize: BEVEL,
                bevelSegments: 4,
                curveSegments: 24,
            },
        );

        geometry.center();

        // Bent like a sheet (z = BEND · x²), its normals turned with it; and
        // a gradient along the diagonal, in its colours.
        const position = geometry.getAttribute('position') as BufferAttribute;
        const normal = geometry.getAttribute('normal') as BufferAttribute;
        const a = new THREE.Color(from);
        const b = new THREE.Color(from).lerp(new THREE.Color(to), lean);
        const c = new THREE.Color();
        const colours = new Float32Array(position.count * 3);

        for (let i = 0; i < position.count; i++) {
            const x = position.getX(i);
            const y = position.getY(i);
            const phi = -Math.atan(2 * BEND * x);
            const nx = normal.getX(i);
            const nz = normal.getZ(i);

            position.setZ(i, position.getZ(i) + BEND * x * x);
            normal.setX(i, nx * Math.cos(phi) + nz * Math.sin(phi));
            normal.setZ(i, -nx * Math.sin(phi) + nz * Math.cos(phi));

            c.copy(a).lerp(b, clamp01((x - y) / (size * 2.4) + 0.5));
            colours.set([c.r, c.g, c.b], i * 3);
        }

        geometry.setAttribute('color', new THREE.BufferAttribute(colours, 3));

        const material = new THREE.MeshPhysicalMaterial({
            vertexColors: true,
            roughness: 0.5,
            metalness: 0.03,
            clearcoat: 0.2,
            clearcoatRoughness: 0.5,
            sheen: 0.35,
            sheenColor: new THREE.Color('#A18EFF'),
            sheenRoughness: 0.6,
            emissive: new THREE.Color('#775AFC'),
            emissiveIntensity: 0,
            side: THREE.DoubleSide,
        });

        materials.push(material);

        return new THREE.Mesh(geometry, material);
    });

    object.add(...fins);
    scene.add(object);

    // ---- The states -----------------------------------------------------------------

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

    // 02's shell: a tetrahedron with a face towards the camera. The three
    // behind meet at the back, each fin's tip at that apex; the front one
    // is swung aside, down and to the left, to show what is inside.
    const edge = 1.75;
    const height = edge * Math.sqrt(2 / 3);
    const front = height / 4;
    const rim3 = [0, 1, 2].map((i) => {
        const a = Math.PI / 2 + (i * 2 * Math.PI) / 3;

        return new THREE.Vector3(
            (Math.cos(a) * edge) / Math.sqrt(3),
            (Math.sin(a) * edge) / Math.sqrt(3),
            front,
        );
    });
    const apex = new THREE.Vector3(0, 0, front - height);
    const faceOf = (points: Vector3[], tip: Vector3): FinState => {
        const centre = points
            .reduce((sum, p) => sum.add(p), new THREE.Vector3())
            .divideScalar(3);
        const z = centre.clone().normalize();
        const y = tip.clone().sub(centre).normalize();
        const x = new THREE.Vector3().crossVectors(y, z);
        const q = new THREE.Quaternion().setFromRotationMatrix(
            new THREE.Matrix4().makeBasis(x, y, z),
        );
        const p = centre.multiplyScalar(1.1);

        return { p: [p.x, p.y, p.z], q };
    };
    const shell: FinState[] = [
        {
            p: [-1.2, -0.85, 0.45],
            r: [-30, 24, 40],
            s: [0.72, 0.72, 0.72],
        },
        ...[0, 1, 2].map((i) =>
            faceOf([rim3[i]!, rim3[(i + 1) % 3]!, apex], apex),
        ),
    ];

    const STATES: State[] = [
        { rot: [8, -12, 0], scale: 1, shift: 0, opacity: 1, fins: PINWHEEL },
        // 01: front wall, rear wall, base, diagonal brace.
        {
            rot: [18, -36, 0],
            scale: 1.08,
            shift: 0,
            opacity: 1,
            fins: [
                { p: [0.22, 0.05, 0.72], r: [0, -6, 0] },
                { p: [0.32, 0.28, -0.78], r: [0, -6, 0], s: [1.1, 1.1, 1] },
                { p: [0.1, -0.78, 0], r: [-90, -8, 0] },
                { p: [-0.72, -0.02, 0], r: [-10, 58, 0] },
            ],
        },
        { rot: [-12, -20, 0], scale: 1.02, shift: 0, opacity: 1, fins: shell },
        // 03: flat and wide — two broad fins facing out, a small third.
        {
            rot: [2, -6, 0],
            scale: 1.1,
            shift: 0.02,
            opacity: 1,
            fins: [
                { p: [-0.32, 0.12, 0.15], r: [0, 0, 92], s: [1.25, 1, 0.5] },
                { p: [-0.85, -0.6, -0.2], r: [0, 0, 180], s: [0.6, 0.6, 0.5] },
                {
                    p: [0.48, -0.1, -0.15],
                    r: [0, 0, -92],
                    s: [0.85, 0.75, 0.5],
                },
                { p: [0.48, -0.1, 0], r: [0, 0, -92], s: [1.2, 1, 0.5] },
            ],
        },
        // Exit: the pinwheel again, smaller, gone.
        { rot: [8, -12, 0], scale: 0.55, shift: 0, opacity: 0, fins: PINWHEEL },
    ];

    const finQuats = STATES.map(({ fins: poses }) =>
        poses.map((pose) => pose.q ?? toQuat(pose.r ?? [0, 0, 0])),
    );
    const turnQuats = STATES.map(({ rot }) => toQuat(rot));

    // ---- 01's guides, 02's orbit, the ground ----------------------------------------

    const dashed = (): Material =>
        new THREE.LineDashedMaterial({
            color: 0x775afc,
            dashSize: 0.06,
            gapSize: 0.05,
            transparent: true,
            opacity: 0,
            depthWrite: false,
        });
    const ellipse = (r: number) =>
        new THREE.BufferGeometry().setFromPoints(
            new THREE.EllipseCurve(0, 0, r, r)
                .getPoints(96)
                .map((v) => new THREE.Vector3(v.x, 0, v.y)),
        );
    const guides = new THREE.Group();
    const guideLines = [
        new THREE.LineLoop(ellipse(1.75), dashed()),
        new THREE.LineLoop(ellipse(1.45), dashed()),
        new THREE.Line(
            new THREE.BufferGeometry().setFromPoints([
                new THREE.Vector3(0, -1.6, 0),
                new THREE.Vector3(0, 1.6, 0),
            ]),
            dashed(),
        ),
    ];

    guideLines[1]!.rotation.set(THREE.MathUtils.degToRad(68), 0, 0.3);
    guideLines.forEach((line) => line.computeLineDistances());
    guides.add(...guideLines);
    object.add(guides);

    const orbit = new THREE.Group();
    const orbitLine = new THREE.LineLoop(
        ellipse(1.5),
        new THREE.LineBasicMaterial({
            color: 0xa18eff,
            transparent: true,
            opacity: 0,
            depthWrite: false,
        }),
    );
    const dotMaterial = new THREE.MeshBasicMaterial({
        color: 0xc9bcff,
        transparent: true,
        opacity: 0,
    });
    const dots = [0, 1, 2].map(
        () =>
            new THREE.Mesh(new THREE.SphereGeometry(0.035, 12, 8), dotMaterial),
    );

    orbit.rotation.set(THREE.MathUtils.degToRad(14), 0, 0.08);
    orbit.add(orbitLine, ...dots);
    object.add(orbit);

    // A soft shadow under it (a violet glow on the indigo).
    const groundCanvas = document.createElement('canvas');

    groundCanvas.width = groundCanvas.height = 128;

    const groundContext = groundCanvas.getContext('2d');

    if (groundContext) {
        const fill = groundContext.createRadialGradient(64, 64, 0, 64, 64, 64);

        fill.addColorStop(0, 'rgba(255, 255, 255, 1)');
        fill.addColorStop(1, 'rgba(255, 255, 255, 0)');
        groundContext.fillStyle = fill;
        groundContext.fillRect(0, 0, 128, 128);
    }

    const groundMaterial = new THREE.MeshBasicMaterial({
        map: new THREE.CanvasTexture(groundCanvas),
        color: 0x775afc,
        transparent: true,
        opacity: 0.2,
        depthWrite: false,
    });
    const ground = new THREE.Mesh(
        new THREE.PlaneGeometry(1, 1),
        groundMaterial,
    );

    ground.renderOrder = -1;
    scene.add(ground);

    // ---- What is drawn around it (HTML) ---------------------------------------------

    const find = (selector: string): HTMLElement | null =>
        section.querySelector<HTMLElement>(selector);
    const ring = find('[data-story-ring]');
    const screens: [
        HTMLElement | null,
        number,
        number,
        number,
        number,
        number,
    ][] = [
        // Element, its state, then width, height, x, y — of the object's height.
        [find('[data-story-screen=saas]'), 2, 0.5, 0.34, 0, 0.04],
        [find('[data-story-screen=site]'), 3, 0.64, 0.42, -0.16, -0.06],
        [find('[data-story-screen=shop]'), 3, 0.42, 0.29, 0.32, 0.2],
    ];
    const callouts = Array.from(
        section.querySelectorAll<HTMLElement>('[data-story-callout]'),
    );
    // 01's callouts, at points on the fins: the front wall's upper corner,
    // the brace's far tip, the base's front corner (in the fin's own units).
    const anchors: [number, number, number][] = [
        [0, 0.55, 0.35],
        [3, -0.55, 0.2],
        [2, 0.55, -0.35],
    ];
    const tmp: Vector3 = new THREE.Vector3();
    let width = 0;
    let height2 = 0;

    const toScreen = (v: Vector3): [number, number] => {
        v.project(camera);

        return [((v.x + 1) / 2) * width, ((1 - v.y) / 2) * height2];
    };

    const show = (el: HTMLElement | null, weight: number): boolean => {
        if (!el) {
            return false;
        }

        el.style.visibility = weight < 0.01 ? 'hidden' : 'visible';
        el.style.opacity = weight.toFixed(3);

        return weight >= 0.01;
    };

    // ---- Pointer, only while a state holds ------------------------------------------

    const pointer = { x: 0, y: 0 };
    const lean = { x: 0, y: 0, steady: 0 };

    if (!reduced && matchMedia('(pointer: fine)').matches) {
        window.addEventListener(
            'pointermove',
            (event) => {
                pointer.x = (event.clientX / window.innerWidth) * 2 - 1;
                pointer.y = (event.clientY / window.innerHeight) * 2 - 1;
            },
            { passive: true },
        );
    }

    // ---- Reduced motion: a cross-fade between states, not a journey ---------------

    const shown = { state: 0, fade: 1 };

    const settle = (target: number): void => {
        if (target === shown.state || gsap.isTweening(shown)) {
            return;
        }

        gsap.timeline()
            .to(shown, { fade: 0, duration: 0.2, ease: 'none' })
            .set(shown, { state: target })
            .to(shown, { fade: 1, duration: 0.2, ease: 'none' });
    };

    // ---- When to draw -----------------------------------------------------------------

    let inView = false;

    new IntersectionObserver(([entry]) => {
        inView = entry?.isIntersecting ?? false;
    }).observe(section);

    const narrow = matchMedia('(max-width: 1023px)');
    const resize = (): void => {
        width = host.clientWidth;
        height2 = host.clientHeight;
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.75));
        renderer.setSize(width, height2, false);
        camera.aspect = width / Math.max(1, height2);
        camera.updateProjectionMatrix();
    };

    new ResizeObserver(resize).observe(host);
    resize();

    const lavender = new THREE.Color(0xa18eff);
    const tmpQ: Quaternion = new THREE.Quaternion();
    const extra: Quaternion = new THREE.Quaternion();
    const still = new THREE.Euler();

    // ---- Each frame -----------------------------------------------------------------

    gsap.ticker.add((time: number) => {
        if (!inView || document.hidden || width === 0) {
            return;
        }

        if (reduced) {
            settle(Math.round(state.p));
        }

        const vh = height2;
        const q = reduced ? shown.state : state.p;
        const i = Math.min(Math.floor(q), STATES.length - 2);
        const k = q - i;
        const a = STATES[i]!;
        const b = STATES[i + 1]!;
        const opacity = lerp(a.opacity, b.opacity, k) * shown.fade;

        renderer.domElement.style.opacity = opacity.toFixed(3);

        // The fins, from one state towards the next.
        fins.forEach((mesh, index) => {
            const pa = a.fins[index]!;
            const pb = b.fins[index]!;
            const sa = pa.s ?? [1, 1, 1];
            const sb = pb.s ?? [1, 1, 1];

            mesh.position.set(
                lerp(pa.p[0], pb.p[0], k),
                lerp(pa.p[1], pb.p[1], k),
                lerp(pa.p[2], pb.p[2], k),
            );
            mesh.quaternion.slerpQuaternions(
                finQuats[i]![index]!,
                finQuats[i + 1]![index]!,
                k,
            );
            mesh.scale.set(
                lerp(sa[0], sb[0], k),
                lerp(sa[1], sb[1], k),
                lerp(sa[2], sb[2], k),
            );
        });

        // How much of each state it is; how much it holds still.
        const intro = near(q, 0, 1.6);
        const software = near(q, 1);
        const saas = near(q, 2);
        const holding = reduced
            ? 1
            : Math.max(
                  near(q, 0, 12),
                  near(q, 1, 12),
                  near(q, 2, 12),
                  near(q, 3, 12),
              );
        const breath = reduced ? 0 : Math.sin((time * Math.PI * 2) / 7);
        const pulse = reduced
            ? 0.5
            : 0.5 + 0.5 * Math.sin((time * Math.PI * 2) / 3.5);

        // Where it is: right of the text (above it on narrow screens).
        const worldH =
            2 *
            Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) *
            camera.position.z;
        const worldW = worldH * camera.aspect;
        const x = narrow.matches ? 0.5 : 0.64 + lerp(a.shift, b.shift, k);
        const y = narrow.matches ? 0.22 : 0.5;
        const size = narrow.matches
            ? Math.min(vh * 0.24, width * 0.55)
            : Math.min(vh * 0.58, width * 0.4);
        const grow = lerp(a.scale, b.scale, k);
        const scale =
            ((size / vh) * worldH * grow * (1 + 0.007 * breath)) / BASE;

        object.position.set(
            (x - 0.5) * worldW,
            (0.5 - y) * worldH + breath * 0.004 * worldH,
            0,
        );
        object.scale.setScalar(scale);

        // Its turn, the pointer's lean, and 02's slow oscillation.
        lean.steady = lerp(lean.steady, holding, 0.06);
        lean.x = lerp(lean.x, pointer.y * 2.5 * lean.steady, 0.04);
        lean.y = lerp(lean.y, pointer.x * 2.5 * lean.steady, 0.04);
        object.quaternion.slerpQuaternions(
            turnQuats[i]!,
            tmpQ.copy(turnQuats[i + 1]!),
            k,
        );
        object.quaternion.premultiply(
            extra.setFromEuler(
                still.set(
                    THREE.MathUtils.degToRad(lean.x),
                    THREE.MathUtils.degToRad(
                        lean.y +
                            (reduced
                                ? 0
                                : 1.5 *
                                  saas *
                                  Math.sin((time * Math.PI * 2) / 9)),
                    ),
                    0,
                ),
            ),
        );

        // Light: the indigo ground of 02 dims the room and lights the edges.
        const dark = look.dark;

        scene.environmentIntensity = lerp(0.8, 0.5, dark);
        hemi.color.set(0xf5f3ff).lerp(lavender, dark);
        key.intensity = lerp(1.4, 0.9, dark);
        rim.intensity = lerp(1.2, 3, dark);
        core.position.copy(object.position);
        core.intensity = saas * (5 + 2 * pulse) * scale;
        materials.forEach((material) => {
            material.emissiveIntensity = saas * (0.05 + 0.07 * pulse);
        });

        // 01's guides, 02's orbit.
        guideLines.forEach((line, n) => {
            (line.material as { opacity: number }).opacity =
                software * (n === 2 ? 0.35 : 0.5);
        });
        guides.visible = software > 0.01;
        (orbitLine.material as { opacity: number }).opacity = saas * 0.3;
        dotMaterial.opacity = saas;
        orbit.visible = saas > 0.01;
        dots.forEach((dot, n) => {
            const angle =
                (reduced ? 0 : (time * Math.PI * 2) / 16) +
                (n * Math.PI * 2) / 3;

            dot.position.set(Math.cos(angle) * 1.5, 0, Math.sin(angle) * 1.5);
        });

        // The ground: under it, a soft violet shadow, stronger on indigo.
        ground.position.set(
            object.position.x,
            object.position.y - 1.4 * scale,
            -1,
        );
        ground.scale.set(3.4 * scale * (1 - 0.03 * breath), 0.5 * scale, 1);
        groundMaterial.opacity = lerp(0.22, 0.45, dark);

        object.updateMatrixWorld(true);

        // Around it, in px: the intro's orbit, the screens, the callouts.
        const [cx, cy] = toScreen(tmp.copy(object.position));
        const px = size * grow;

        if (ring && show(ring, narrow.matches ? 0 : intro * opacity)) {
            ring.style.width =
                ring.style.height = `${(px * 1.35).toFixed(1)}px`;
            ring.style.transform = `translate3d(${cx.toFixed(1)}px, ${cy.toFixed(1)}px, 0) translate(-50%, -50%)`;
        }

        for (const [el, at, w, h, dx, dy] of screens) {
            if (el && show(el, near(q, at, 3) * opacity)) {
                el.style.width = `${(w * px).toFixed(1)}px`;
                el.style.height = `${(h * px).toFixed(1)}px`;
                el.style.transform = `translate3d(${(cx + dx * px).toFixed(1)}px, ${(cy + dy * px).toFixed(1)}px, 0) translate(-50%, -50%)`;
            }
        }

        const callout = narrow.matches ? 0 : near(q, 1, 3) * opacity;

        callouts.forEach((el, n) => {
            if (!show(el, callout)) {
                return;
            }

            const [index, ax, ay] = anchors[n]!;
            const [size0] = FINS[index]!;
            const [sx, sy] = toScreen(
                fins[index]!.localToWorld(
                    tmp.set(ax * size0, ay * size0, DEPTH / 2 + BEVEL),
                ),
            );

            el.style.transform = `translate3d(${sx.toFixed(1)}px, ${sy.toFixed(1)}px, 0)`;
        });

        renderer.render(scene, camera);
    });
}

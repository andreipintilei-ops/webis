import type { Mesh, Quaternion } from 'three';
import { loadGsap, prefersReducedMotion } from '@/public/gsap';
import {
    BRAND,
    clamp01,
    hasWebGL,
    lerp,
    outlineOf,
    PIECES,
    SLAB_INDEX,
    slabOf,
    SQUARE_ROUND,
    THICK,
    W,
} from '@/public/plates';
import type { Vec } from '@/public/plates';

/**
 * The closing panel's own W, where there is no stage for the plates to come
 * from (components/site/cta-panel, `standalone`; /portofoliu): the five
 * plates as the home page's finale leaves them — rounded squares in the
 * logo's colours, matte, lit softly (js/public/shapeshifter.ts, its last
 * form) — drawn in the panel's slot.
 *
 * It forms out of nowhere, scrubbed with the scroll as the panel comes up:
 * each plate flies in from far behind the panel, small and turned, one after
 * another from the left, and lands in its place in the W. Then each bobs on
 * its own, a beat after the last. Renders only while the panel is on
 * screen. Reduced motion: formed from the start, still. Without WebGL: the
 * W in CSS (`stage-finale--still`).
 */

/** Each plate starts this far behind the panel (world units)… */
const DEPTH = 26;
/** …this much turned (degrees, per axis), and a little apart. */
const TURN: Vec[] = [
    [-70, 110, -160],
    [60, -90, 140],
    [-50, 130, 200],
    [80, -120, -130],
    [-60, 100, 170],
];
/** Each plate's share of the scroll, and how far after the last it starts. */
const SPAN = 0.6;
const STAGGER = 0.1;

export function initFinaleW(): void {
    document
        .querySelectorAll<HTMLElement>('[data-finale-w]')
        .forEach((panel) => void setUp(panel));
}

async function setUp(panel: HTMLElement): Promise<void> {
    const slot = panel.querySelector<HTMLElement>('[data-stage-slot]');

    if (!slot) {
        return;
    }

    if (!hasWebGL()) {
        panel.classList.add('stage-finale--still');

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

    let renderer: InstanceType<typeof THREE.WebGLRenderer>;

    try {
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    } catch {
        panel.classList.add('stage-finale--still');

        return;
    }

    renderer.setClearColor(0x000000, 0);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.domElement.className = 'stage-finale__canvas';
    slot.append(renderer.domElement);

    // The home page's scene as its W stands on the panel: the same lens,
    // the softened light it ends on (no violet rim to tint the colours).
    const scene = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);

    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environmentIntensity = 0.35;

    const camera = new THREE.PerspectiveCamera(18, 1, 0.1, 100);

    camera.position.set(0, 0, 30);

    const hemi = new THREE.HemisphereLight(0xf5f3ff, 0x1f2868, 0.55);
    const key = new THREE.DirectionalLight(0xffffff, 0.95);
    const rim = new THREE.DirectionalLight(0xa44cee, 0.3);

    key.position.set(-5, 7, 8);
    rim.position.set(6, -3, -7);
    scene.add(hemi, key, rim);

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

    const object = new THREE.Group();
    const plates: { mesh: Mesh; from: Quaternion; to: Quaternion }[] =
        PIECES.map(([size], index) => {
            const geometry = new THREE.BufferGeometry();
            const square = slabOf(
                outlineOf(4, size, SQUARE_ROUND, Math.PI / 4),
                size * THICK,
            );

            geometry.setIndex(SLAB_INDEX);
            geometry.setAttribute(
                'position',
                new THREE.BufferAttribute(square.position, 3),
            );
            geometry.setAttribute(
                'normal',
                new THREE.BufferAttribute(square.normal, 3),
            );

            // Flat and matte, lit a little from within: as flat as the logo.
            const colour = new THREE.Color(BRAND[index]);
            const mesh = new THREE.Mesh(
                geometry,
                new THREE.MeshPhysicalMaterial({
                    color: colour,
                    emissive: colour,
                    emissiveIntensity: 0.2,
                    roughness: 0.95,
                    metalness: 0,
                    specularIntensity: 0.1,
                }),
            );
            const pose = W[index]!;

            mesh.frustumCulled = false;
            object.add(mesh);

            return {
                mesh,
                from: toQuat([
                    (pose.r?.[0] ?? 0) + TURN[index]![0],
                    (pose.r?.[1] ?? 0) + TURN[index]![1],
                    (pose.r?.[2] ?? 0) + TURN[index]![2],
                ]),
                to: toQuat(pose.r ?? [0, 0, 0]),
            };
        });

    scene.add(object);

    // ---- Size: the W across about 60% of the slot, never taller than it --

    /** The W's width and height in world units (its plates on their corners). */
    const W_WIDTH = 5.75;
    const W_HEIGHT = 2.85;

    const resize = (): void => {
        const width = slot.clientWidth;
        const height = slot.clientHeight;

        if (width === 0 || height === 0) {
            return;
        }

        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();

        const worldH =
            2 *
            Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) *
            camera.position.z;
        const pxPerUnit = height / worldH;
        const target = Math.min(
            width * 0.6,
            640,
            (height * 0.78 * W_WIDTH) / W_HEIGHT,
        );

        object.scale.setScalar(target / (W_WIDTH * pxPerUnit));
        draw();
    };

    // ---- Forming, with the scroll; then the bob -------------------------

    const state = { form: reduced ? 1 : 0 };
    let visible = false;
    let frame = 0;

    const render = (time: number): void => {
        frame = 0;

        plates.forEach(({ mesh, from, to }, index) => {
            const pose = W[index]!;
            const scale = pose.s ?? [1, 1, 1];
            const t = clamp01((state.form - index * STAGGER) / SPAN);
            const eased = 1 - Math.pow(1 - t, 3);
            const bob = reduced
                ? 0
                : Math.sin((time / 1000) * ((Math.PI * 2) / 5) + index * 0.9) *
                  0.05 *
                  eased;

            mesh.position.set(
                pose.p[0] * lerp(1.8, 1, eased),
                pose.p[1] * lerp(1.8, 1, eased) + bob,
                lerp(-DEPTH, pose.p[2], eased),
            );
            mesh.quaternion.slerpQuaternions(from, to, eased);
            mesh.scale.set(
                scale[0] * eased,
                scale[1] * eased,
                scale[2] * eased,
            );
            mesh.visible = eased > 0.001;
        });

        renderer.render(scene, camera);

        // Keeps going while on screen: the plates bob once they stand.
        if (visible && !reduced) {
            frame = requestAnimationFrame(render);
        }
    };

    function draw(): void {
        frame ||= requestAnimationFrame(render);
    }

    new ResizeObserver(resize).observe(slot);
    resize();

    new IntersectionObserver(([entry]) => {
        visible = entry?.isIntersecting ?? false;

        if (visible) {
            draw();
        }
    }).observe(panel);

    if (!reduced) {
        gsap.to(state, {
            form: 1,
            ease: 'none',
            onUpdate: draw,
            scrollTrigger: {
                trigger: panel,
                start: 'top 85%',
                end: 'top 20%',
                scrub: 1,
            },
        });
    }
}

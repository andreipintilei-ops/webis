import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The clients' wall (components/site/logo-wall.blade.php;
 * css/site/logo-wall.css): an endless field of logo tiles, the columns a
 * half tile apart, the diamond in its middle — 1·2·3·2·1 tiles at rest —
 * whole, the rest around it smaller and faded, fainter toward either side
 * of the page (the stage's mask): a hint there is more to drag in. A tile is drawn
 * by its distance from the middle, measured so that the diamond's tiles are
 * all at 1 or less and the next ring at 2; so as the field moves, logos
 * come up to full at the diamond's edges and fade at the other side.
 *
 * The field is a pool of tiles (16 columns × 7 rows) that wrap around as it
 * moves, each taking the logo of the place it lands on; the logos repeat
 * across the field, no two neighbours alike.
 *
 * As the wall scrolls into view it forms from its middle out, the tiles
 * flying in from further out and turning into place (scrubbed with the
 * scroll). Drag it — a flick carries on — and it settles on the nearest
 * tile; click a tile, or with the wall in focus use the arrow keys, to
 * bring one to the middle. Reduced motion: formed from the start, the moves
 * instant.
 */

/** The pool: enough for the stage's width either side; columns even. */
const COLUMNS = 16;
const ROWS = 7;

/** A move: long, on an expo-out curve, as the reviews carousel's. */
const MOVE_MS = 800;

/** How far a flick carries: its speed (px/ms) times this. */
const FLING_MS = 180;

interface Tile {
    element: HTMLElement;
    column: number;
    row: number;
    /** Its place in the field, and the logo shown for it. */
    i: number;
    j: number;
    logo: number;
}

const mod = (value: number, by: number): number => ((value % by) + by) % by;

const clamp01 = (value: number): number => Math.min(1, Math.max(0, value));

export function initLogoWalls(): void {
    document
        .querySelectorAll<HTMLElement>('[data-logo-wall]')
        .forEach((stage) => void setUp(stage));
}

async function setUp(stage: HTMLElement): Promise<void> {
    const field = stage.querySelector<HTMLElement>('[data-logo-wall-field]');
    const logos = Array.from(
        stage.querySelectorAll<HTMLElement>('[data-logo-wall-logos] > li'),
    );

    if (!field || logos.length === 0) {
        return;
    }

    const still = prefersReducedMotion();
    const images = logos.map((logo) => logo.querySelector('img'));

    /*
      The field's offset from the middle, in pitches (one tile and its gap),
      so a resize keeps the same tile in the middle. `form`: how far the wall
      has formed, 0–1.
    */
    const state = { x: 0, y: 0, form: still ? 1 : 0 };
    let pitch = 0;
    let frame = 0;
    const tiles: Tile[] = [];

    for (let row = 0; row < ROWS; row++) {
        for (let column = 0; column < COLUMNS; column++) {
            const element = document.createElement('div');

            element.className = 'logo-wall__tile';
            field.append(element);
            tiles.push({ element, column, row, i: 0, j: 0, logo: -1 });
        }
    }

    // A column's tiles sit half a tile lower in every other column.
    const shift = (i: number): number => (mod(i, 2) === 1 ? 0.5 : 0);

    // No two neighbours alike, with three logos or more.
    const logoAt = (i: number, j: number): number =>
        mod(i * 2 + j, logos.length);

    // From the middle, in the diamond's measure: 1 at its edge, 2 a ring out.
    const distance = (x: number, y: number): number =>
        Math.abs(x) / 2 + Math.abs(y);

    const render = (): void => {
        frame = 0;

        for (const tile of tiles) {
            // The one place, of those this tile stands for, nearest the middle.
            tile.i =
                tile.column +
                COLUMNS *
                    Math.ceil((-COLUMNS / 2 - state.x - tile.column) / COLUMNS);

            const lower = shift(tile.i);

            tile.j =
                tile.row +
                ROWS *
                    Math.ceil((-ROWS / 2 - state.y - lower - tile.row) / ROWS);

            const x = tile.i + state.x;
            const y = tile.j + lower + state.y;
            const d = distance(x, y);
            // Past the diamond's edge: a little smaller and faded, the stage's
            // radial mask fading them further toward its edges.
            const beyond = clamp01((d - 1) / 0.5);
            const size = 1 - beyond * 0.12;
            // Above and below the diamond's three rows, nearly gone.
            const fade =
                (1 - beyond * 0.55) * clamp01((1.6 - Math.abs(y)) / 0.6);

            // Forming: the middle first, the rings after it, one by one.
            const formed = clamp01((state.form - d * 0.12) / 0.55);
            const ease = 1 - Math.pow(1 - formed, 3);
            const scale = size * ease;
            const spread = 1 + (1 - ease) * 0.7;
            const turn = (1 - ease) * (mod(tile.i + tile.j, 2) ? 14 : -14);

            const logo = logoAt(tile.i, tile.j);

            if (scale < 0.01) {
                tile.element.style.visibility = 'hidden';
                continue;
            }

            if (logo !== tile.logo) {
                tile.logo = logo;
                const image = images[logo]?.cloneNode() as
                    | HTMLImageElement
                    | undefined;

                if (image) {
                    image.alt = '';
                    image.loading = 'eager';
                    image.className = 'logo-wall__ink';
                    image.style.setProperty(
                        '--logo-scale',
                        logos[logo]!.style.getPropertyValue('--logo-scale'),
                    );
                }

                tile.element.replaceChildren(...(image ? [image] : []));
            }

            tile.element.style.visibility = '';
            tile.element.style.opacity = String(fade * Math.min(1, ease * 1.5));
            tile.element.style.transform = `translate(-50%, -50%) translate3d(${x * pitch * spread}px, ${y * pitch * spread}px, 0) rotate(${turn}deg) scale(${scale})`;
            tile.element.dataset.i = String(tile.i);
            tile.element.dataset.j = String(tile.j);
        }
    };

    const draw = (): void => {
        frame ||= requestAnimationFrame(render);
    };

    const measure = (): void => {
        // A phone shows the diamond and the next columns' edges.
        pitch = Math.min(200, stage.clientWidth / 4.64);
        stage.style.setProperty('--pitch', `${pitch}px`);
        draw();
    };

    new ResizeObserver(measure).observe(stage);
    measure();
    stage.classList.add('is-live');

    /* ---- Moves ----------------------------------------------------------- */

    let moving = 0;

    // The offset putting place (i, j) in the middle.
    const offsetOf = (i: number, j: number): { x: number; y: number } => ({
        x: -i,
        y: -(j + shift(i)),
    });

    // The place nearest the middle for an offset.
    const placeAt = (x: number, y: number): { i: number; j: number } => {
        const i = Math.round(-x);

        return { i, j: Math.round(-y - shift(i)) };
    };

    const moveTo = (i: number, j: number): void => {
        const to = offsetOf(i, j);

        cancelAnimationFrame(moving);

        // Already there (a click on the middle one), or no motion: move now.
        if (
            still ||
            Math.abs(to.x - state.x) + Math.abs(to.y - state.y) < 0.001
        ) {
            Object.assign(state, to);
            draw();

            return;
        }

        const from = { x: state.x, y: state.y };
        const start = performance.now();

        const step = (now: number): void => {
            const t = Math.min(1, (now - start) / MOVE_MS);
            const eased = t === 1 ? 1 : 1 - Math.pow(2, -10 * t);

            state.x = from.x + (to.x - from.x) * eased;
            state.y = from.y + (to.y - from.y) * eased;
            render();
            moving = t < 1 ? requestAnimationFrame(step) : 0;
        };

        moving = requestAnimationFrame(step);
    };

    /* ---- Drag, flick and click --------------------------------------------- */

    let drag: {
        id: number;
        x: number;
        y: number;
        moved: number;
        samples: { x: number; y: number; t: number }[];
    } | null = null;

    stage.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) {
            return;
        }

        cancelAnimationFrame(moving);
        drag = {
            id: event.pointerId,
            x: event.clientX,
            y: event.clientY,
            moved: 0,
            samples: [
                { x: event.clientX, y: event.clientY, t: event.timeStamp },
            ],
        };
        stage.setPointerCapture(event.pointerId);
        stage.classList.add('is-dragging');
    });

    stage.addEventListener('pointermove', (event) => {
        if (!drag || event.pointerId !== drag.id) {
            return;
        }

        const dx = event.clientX - drag.x;
        const dy = event.clientY - drag.y;

        drag.x = event.clientX;
        drag.y = event.clientY;
        drag.moved += Math.abs(dx) + Math.abs(dy);

        drag.samples.push({
            x: event.clientX,
            y: event.clientY,
            t: event.timeStamp,
        });
        drag.samples = drag.samples.filter((s) => event.timeStamp - s.t < 100);

        state.x += dx / pitch;
        state.y += dy / pitch;
        draw();
    });

    const release = (event: PointerEvent): void => {
        if (!drag || event.pointerId !== drag.id) {
            return;
        }

        const { moved, samples } = drag;

        drag = null;
        stage.classList.remove('is-dragging');

        // Hardly moved: a click, on a tile or beside them.
        if (moved < 6) {
            const tile = (event.target as Element).closest<HTMLElement>(
                '.logo-wall__tile',
            );

            if (tile) {
                moveTo(Number(tile.dataset.i), Number(tile.dataset.j));

                return;
            }
        }

        const first = samples[0];
        const last = samples[samples.length - 1];
        const time = Math.max(16, last.t - first.t);
        const carry = (event.type === 'pointerup' ? FLING_MS : 0) / time;
        const place = placeAt(
            state.x + ((last.x - first.x) * carry) / pitch,
            state.y + ((last.y - first.y) * carry) / pitch,
        );

        moveTo(place.i, place.j);
    };

    stage.addEventListener('pointerup', release);
    stage.addEventListener('pointercancel', release);

    stage.addEventListener('keydown', (event) => {
        const { i, j } = placeAt(state.x, state.y);
        // Sideways, to the neighbour above or below by the column's shift.
        const side = (to: number): number =>
            Math.round(j + shift(i) - shift(to));
        const target = {
            ArrowLeft: { i: i - 1, j: side(i - 1) },
            ArrowRight: { i: i + 1, j: side(i + 1) },
            ArrowUp: { i, j: j - 1 },
            ArrowDown: { i, j: j + 1 },
        }[event.key];

        if (target) {
            event.preventDefault();
            moveTo(target.i, target.j);
        }
    });

    /* ---- Forming, with the scroll ---------------------------------------- */

    if (still) {
        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);
    gsap.to(state, {
        form: 1,
        ease: 'none',
        onUpdate: draw,
        scrollTrigger: {
            trigger: stage,
            // Over a long stretch, and trailing the scroll, so it plays out
            // even when the page is scrolled past quickly.
            start: 'top 90%',
            end: 'bottom 55%',
            scrub: 1.2,
        },
    });
}

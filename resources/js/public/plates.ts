/**
 * The Webis plates (js/public/shapeshifter.ts, js/public/finale-w.ts): the
 * five pieces, their colours, the mesh one outline is rolled into, and the
 * W they stand as. Pure data and geometry, no Three.js: each script builds
 * its own meshes from these.
 */

export type Vec = [number, number, number];

export interface Pose {
    p: Vec;
    /** Degrees, applied as: twist about the slab's own axis (Y), tilt (X),
     *  then spin in the screen's plane (Z). */
    r?: Vec;
    s?: Vec;
    /** The outline: 0 a rounded triangle, 1 a rounded square. */
    m?: number;
}
/** The plates: size (to a corner), the colour at its core, halfway out, at
 *  its edge, and whether it is a frosted one. Each a purple of its own —
 *  indigo, violet, orchid, periwinkle, magenta — none of them light: on a
 *  phone the white text runs over them (a white plate there washed it out). */
export const PIECES: [number, string, string, string, boolean][] = [
    [1.15, '#0E0B33', '#2A1E8E', '#6B4BFF', false],
    [1.06, '#2C2390', '#5B45E6', '#A07BFF', false],
    [1, '#5A3FE0', '#8E63F5', '#D08CF5', false],
    [0.94, '#1C2584', '#4A5BEA', '#8E9BFF', false],
    [0.9, '#3A1A86', '#9148E0', '#C98BF5', true],
];

/** The Webis logo's colours, plate by plate as they stand in the W (left to
 *  right): the plates take them on as they settle on the panel. */
export const BRAND = ['#5B5EA6', '#62B246', '#FFE000', '#F4A024', '#F24F55'];

// ---- The plate's mesh ------------------------------------------------------

export const TAU = Math.PI * 2;
/** Samples around the outline, rings across a face, rings around the rim. */
export const COLUMNS = 96;
const FACE = 3;
const RIM = 12;
const ROWS = 2 * FACE + RIM + 1;
/** Half the thickness — and the rim's radius, so the edge rolls right over
 *  (× the plate's size). Thin: these are plates, not blocks. */
export const THICK = 0.045;
/** How much each plate is cupped (z = BEND · x²), as the reference's are. */
const BEND = 0.1;
export const TRI_ROUND = 0.22;
export const SQUARE_ROUND = 0.3;
export const clamp01 = (v: number): number => Math.min(1, Math.max(0, v));
export const lerp = (a: number, b: number, t: number): number =>
    a + (b - a) * t;
/**
 * A convex rounded regular polygon, resampled at equal arc length going
 * counter-clockwise from 12 o'clock: the points, then their outward normals.
 * Two shapes sampled this way share a point-for-point mapping, which is what
 * lets either one morph into the other.
 */
export const outlineOf = (
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
export const slabOf = (
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

export const SLAB_INDEX: number[] = [];

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
/** The W: five rounded squares on their corners, up–down–up–down–up. */
const W_STEP = 0.98;
const W_RISE = 0.49;
const W_SIZE = 0.9;
export const W: Pose[] = PIECES.map(([size], i) => {
    const up = i % 2 === 0;
    const k = W_SIZE / size;

    return {
        p: [(i - 2) * W_STEP, up ? W_RISE : -W_RISE, up ? 0 : -0.05],
        r: [up ? -3 : 3, (i - 2) * 3, 45],
        s: [k, k, 1],
        m: 1,
    };
});
/** WebGL drawn by a GPU: in software (no GPU, a blocked driver) the scenes
 *  would run on the CPU and stall the page, so the stills show instead. */
export const hasWebGL = (): boolean => {
    try {
        const canvas = document.createElement('canvas');
        const options = { failIfMajorPerformanceCaveat: true };

        return !!(
            canvas.getContext('webgl2', options) ??
            canvas.getContext('webgl', options)
        );
    } catch {
        return false;
    }
};

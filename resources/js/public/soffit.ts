/**
 * "Soffit" — an animated WebGL2 gradient: one fullscreen triangle, one
 * fragment shader, GLSL ES 3.00, no library. Mounted on every
 * <canvas data-soffit> (the hero's "gradient animat" background).
 *
 * The contract, as authored:
 * - Every transformation happens in the shader. Per frame the CPU only steps
 *   the pointer followers, uploads uniforms and issues one drawArrays.
 * - One uniform per CONFIG key (`colorA` → `uColorA`); colours as vec3 0–1.
 * - Opaque canvas, output clamped and dithered (the dither prevents banding).
 * - Device pixel ratio capped at CONFIG.maxDpr — the cost is quadratic in it.
 * - The pointer is felt, not tracked: a fast lead (0.105) chased by a slower
 *   body (0.043), stepped by elapsed time so the lag is the same at 60/144 Hz.
 * - The shader clock accumulates a clamped frame interval rather than wall
 *   time, so a backgrounded tab costs a pause, never a lurch.
 *
 * Adapted from full-window to element: the canvas is sized from its own box
 * (ResizeObserver) and the pointer is measured against it. It stops drawing
 * while off-screen or in a hidden tab, and draws a single still frame for
 * visitors who prefer reduced motion. To recolour or retune, change CONFIG
 * (PALETTES for the colours) — never the shader.
 *
 * One Webis addition, marked as such in the shader: the cursor stirs the
 * field locally (STIR). At strength and swirl 0 the picture is the original.
 */

const CONFIG = {
    bgColor: '#01225e',
    colorA: '#2b7be8',
    colorB: '#2bc8e8',
    colorC: '#4fe0d8',
    colorD: '#7fe8a0',
    scale: 1,
    speed: 0.33,
    tilt: 1.87,
    rock: 0.12,
    horizon: 0.36,
    breathe: 0.29,
    spread: 0.41,
    curve: 3.32,
    direct: 0.97,
    bounce: 0.38,
    bounceCurve: 4.25,
    spillCentre: 0.3,
    spillWidth: 2.18,
    spillFloor: 0.26,
    amount: 0.2,
    warp: 2.58,
    warpScale: 0.78,
    flow: 0.475,
    roughness: 0.29,
    lacunarity: 1.99,
    motes: 0.074,
    moteScale: 7,
    ambient: 0.24,
    contrast: 2.45,
    midpoint: 0.57,
    sink: 0.24,
    glow: 0.38,
    grain: 0.034,
    grainAnim: 0,
    dither: 0.58,
    vignette: 0.21,
    steer: -0.13,
    lift: 0.11,
    sweep: 0.5,
    cursor: 1,
    parallax: 0.0137,
    maxDpr: 1,
} as const;

const VERT = `#version 300 es
void main() {
  vec2 p = vec2((gl_VertexID << 1) & 2, gl_VertexID & 2);
  gl_Position = vec4(p * 2.0 - 1.0, 0.0, 1.0);
}`;

const FRAG = `#version 300 es
precision highp float;
out vec4 fragColor;

uniform vec2  iResolution;
uniform float iTime;
uniform vec2  iMouse;          // aspect-corrected units, same space as uv
uniform float uScale;          // zoom of the whole picture — uv and the pointer together

uniform vec3  uBg, uColorA, uColorB, uColorC, uColorD;
uniform float uSpeed, uTilt, uRock, uHorizon, uBreathe, uSpread, uCurve, uDirect;
uniform float uBounce, uBounceCurve;
uniform float uSpillCentre, uSpillWidth, uSpillFloor;
uniform float uAmount, uWarp, uWarpScale, uFlow, uRoughness, uLacunarity, uMotes, uMoteScale;
uniform float uAmbient, uContrast, uMidpoint, uSink, uGlow;
uniform float uGrain, uDither, uVignette;
uniform float uSteer, uLift, uSweep, uParallax;

// ---- Webis addition: the cursor stirs the field locally (see STIR). Additive:
// with uStirStrength and uStirSwirl at 0 the picture is exactly the original.
uniform vec2  uStirPos;        // the cursor, same space as iMouse (unsmoothed lead)
uniform vec2  uStirVel;        // its velocity per 60 Hz frame, decaying after it stops
uniform float uStirRadius, uStirStrength, uStirSwirl;

#define OCTAVES 4

vec2 hash2(vec2 p) {
  p = vec2(dot(p, vec2(127.1, 311.7)), dot(p, vec2(269.5, 183.3)));
  return -1.0 + 2.0 * fract(sin(p) * 43758.5453123);
}

float snoise(vec2 p) {
  const float K1 = 0.366025404, K2 = 0.211324865;
  vec2 i = floor(p + (p.x + p.y) * K1);
  vec2 a = p - i + (i.x + i.y) * K2;
  float m = step(a.y, a.x);
  vec2 o = vec2(m, 1.0 - m);
  vec2 b = a - o + K2;
  vec2 c = a - 1.0 + 2.0 * K2;
  vec3 h = max(0.5 - vec3(dot(a, a), dot(b, b), dot(c, c)), 0.0);
  vec3 n = h * h * h * h * vec3(dot(a, hash2(i)), dot(b, hash2(i + o)), dot(c, hash2(i + 1.0)));
  return dot(n, vec3(70.0));
}

float fbm(vec2 p) {
  float v = 0.0, amp = 0.5;
  for (int i = 0; i < OCTAVES; i++) {
    v += amp * snoise(p);
    p *= uLacunarity;
    amp *= uRoughness;
  }
  return v;
}

vec3 ramp4(float t) {
  vec3 c = mix(uColorA, uColorB, smoothstep(0.00, 0.36, t));
  c = mix(c, uColorC, smoothstep(0.32, 0.70, t));
  c = mix(c, uColorD, smoothstep(0.66, 1.00, t));
  return c;
}

float triDither(vec2 fc) {
  float a = fract(sin(dot(fc, vec2(12.9898, 78.233))) * 43758.5453);
  float b = fract(sin(dot(fc + 17.0, vec2(12.9898, 78.233))) * 43758.5453);
  return (a + b - 1.0) / 255.0;
}

uniform float uGrainAnim;
float houseGrain(vec2 fc) {
  uvec2 q = uvec2(fc) * uvec2(1597334677u, 3812015801u)
          + uint(floor(iTime * 24.0 * uGrainAnim)) * 2654435769u;
  uint n = q.x ^ q.y; n = n * 1664525u + 1013904223u; n ^= n >> 16u; n *= 2246822519u; n ^= n >> 13u;
  float a = float(n & 0xffffu) / 65535.0;
  n *= 3266489917u; n ^= n >> 16u;
  float b = float(n & 0xffffu) / 65535.0;
  return a + b - 1.0;
}
void main() {
  vec2 uv = (gl_FragCoord.xy - 0.5 * iResolution) / iResolution.y;
  uv *= uScale;
  vec2 iM = iMouse * uScale;
  float t = iTime * uSpeed;

  vec2 p = uv - iM * uParallax;

  // Stir: around the cursor the field is dragged along with the motion and
  // turned a little about it, fading out with distance (Gaussian). Driven by
  // velocity, so a still cursor leaves the picture alone.
  {
    vec2 sd = uv - uStirPos * uScale;
    float sr = max(uStirRadius, 0.01);
    float sf = exp(-dot(sd, sd) / (sr * sr));
    p -= (uStirVel * uStirStrength + vec2(-sd.y, sd.x) * uStirSwirl * length(uStirVel)) * sf;
  }

  float tilt = uTilt + sin(t * 0.13) * uRock + iM.x * uSteer;
  vec2 dir = vec2(cos(tilt), sin(tilt));
  float axis = dot(p, dir);
  float across = dot(p, vec2(-dir.y, dir.x));

  vec2 q = vec2(fbm(p * uWarpScale + vec2(0.0, t * uFlow)),
                fbm(p * uWarpScale + vec2(5.2, 1.3) - t * uFlow * 0.7));
  float air = fbm(p + uWarp * q + vec2(t * 0.12, -t * 0.09)) * 0.5 + 0.5;

  float horizon = uHorizon + sin(t * 0.09 + 2.1) * uBreathe - iM.y * uLift;
  float alt = clamp(0.5 + (axis - horizon) * uSpread + (air - 0.5) * uAmount, 0.0, 1.0);

  float ac = (across - uSpillCentre - iM.x * uSweep) / max(0.05, uSpillWidth);
  float spill = mix(uSpillFloor, 1.0, exp(-ac * ac));

  float direct = pow(alt, max(0.05, uCurve)) * uDirect * spill;

  float bounce = uBounce * pow(1.0 - alt, max(0.05, uBounceCurve));

  float f = uAmbient + direct + bounce;
  f += uMotes * snoise(p * uMoteScale + vec2(-t * 0.5, t * 0.35)) * 0.5 * alt;

  f = clamp((f - uMidpoint) * uContrast + 0.5, 0.0, 1.0);

  vec3 col = ramp4(f);
  col += uColorD * uGlow * pow(f, 4.0);
  col = mix(uBg, col, smoothstep(0.0, max(0.01, uSink), f) * 0.90 + 0.10);

  col *= 1.0 - uVignette * dot(uv, uv);
  { float hgL = clamp(dot(col, vec3(0.299, 0.587, 0.114)), 0.0, 1.0);
    col += houseGrain(gl_FragCoord.xy) * uGrain * mix(1.0, 4.0 * hgL * (1.0 - hgL), 0.6); }
  col += triDither(gl_FragCoord.xy) * uDither;

  fragColor = vec4(clamp(col, 0.0, 1.0), 1.0);
}`;

/**
 * Webis addition, not in the original spec: the cursor stirs the field
 * around itself, dragging it along with the motion and turning it slightly,
 * in proportion to the cursor's speed — a still cursor leaves it alone.
 *
 * - radius: reach, in screen heights (Gaussian falloff)
 * - strength: how far the field is dragged per unit of cursor speed
 * - swirl: how much it turns about the cursor, also per unit of speed
 */
const STIR_PRESETS = {
    off: { radius: 0.3, strength: 0, swirl: 0 },
    soft: { radius: 0.22, strength: 1.5, swirl: 6 },
    medium: { radius: 0.3, strength: 3, swirl: 12 },
    strong: { radius: 0.4, strength: 5, swirl: 20 },
} as const;

const STIR = {
    // TEMPORARY, while choosing: ?stir=off|soft|medium|strong overrides this.
    preset: 'medium' as keyof typeof STIR_PRESETS,
    // How fast the stir point chases the cursor (per 60 Hz frame).
    follow: 0.3,
    // How quickly the wake settles once the cursor slows (per 60 Hz frame).
    fade: 0.07,
    // Speed cap (screen heights per frame), so a flick cannot tear the field.
    maxSpeed: 0.06,
    // A jump bigger than this (the cursor re-entering elsewhere) moves the
    // stir point without stirring.
    jump: 0.25,
};

function stirPreset(): (typeof STIR_PRESETS)[keyof typeof STIR_PRESETS] {
    const asked = new URLSearchParams(window.location.search).get('stir');

    return asked && asked in STIR_PRESETS
        ? STIR_PRESETS[asked as keyof typeof STIR_PRESETS]
        : STIR_PRESETS[STIR.preset];
}

/** CONFIG keys uploaded as floats, in uniform-name form. */
const FLOAT_KEYS = [
    'scale',
    'speed',
    'tilt',
    'rock',
    'horizon',
    'breathe',
    'spread',
    'curve',
    'direct',
    'bounce',
    'bounceCurve',
    'spillCentre',
    'spillWidth',
    'spillFloor',
    'amount',
    'warp',
    'warpScale',
    'flow',
    'roughness',
    'lacunarity',
    'motes',
    'moteScale',
    'ambient',
    'contrast',
    'midpoint',
    'sink',
    'glow',
    'grain',
    'grainAnim',
    'dither',
    'vignette',
    'steer',
    'lift',
    'sweep',
    'parallax',
] as const;

/**
 * The colours, per canvas (`data-palette`, from the hero's background):
 * "blue" is CONFIG's own; "violet" swaps in the old site's purples
 * (webis.ro: deep indigo #1f2868 into #775afc and #a44cee), lightening to
 * lavender for the glow. Everything else in CONFIG is shared.
 */
const PALETTES = {
    blue: {
        uBg: CONFIG.bgColor,
        uColorA: CONFIG.colorA,
        uColorB: CONFIG.colorB,
        uColorC: CONFIG.colorC,
        uColorD: CONFIG.colorD,
    },
    violet: {
        uBg: '#1f2868',
        uColorA: '#775afc',
        uColorB: '#a44cee',
        uColorC: '#c98bf5',
        uColorD: '#efdcff',
    },
} as const;

function paletteOf(
    canvas: HTMLCanvasElement,
): (typeof PALETTES)[keyof typeof PALETTES] {
    return canvas.dataset.palette === 'violet'
        ? PALETTES.violet
        : PALETTES.blue;
}

function hexToVec3(hex: string): [number, number, number] {
    const n = parseInt(hex.slice(1), 16);

    return [((n >> 16) & 255) / 255, ((n >> 8) & 255) / 255, (n & 255) / 255];
}

export function initSoffit(): void {
    for (const canvas of document.querySelectorAll<HTMLCanvasElement>(
        'canvas[data-soffit]',
    )) {
        mount(canvas);
    }
}

function mount(canvas: HTMLCanvasElement): void {
    const gl = canvas.getContext('webgl2', {
        alpha: false,
        antialias: false,
        depth: false,
        stencil: false,
        powerPreference: 'high-performance',
    });

    // No WebGL2: the section's background colour (the gradient's base) stays.
    if (!gl) {
        return;
    }

    const compile = (type: number, source: string): WebGLShader => {
        const shader = gl.createShader(type);

        if (!shader) {
            throw new Error('[soffit] Could not create a shader.');
        }

        gl.shaderSource(shader, source);
        gl.compileShader(shader);

        if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
            throw new Error(`[soffit] ${gl.getShaderInfoLog(shader) ?? ''}`);
        }

        return shader;
    };

    const program = gl.createProgram();
    gl.attachShader(program, compile(gl.VERTEX_SHADER, VERT));
    gl.attachShader(program, compile(gl.FRAGMENT_SHADER, FRAG));
    gl.linkProgram(program);

    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
        throw new Error(`[soffit] ${gl.getProgramInfoLog(program) ?? ''}`);
    }

    gl.useProgram(program);
    gl.bindVertexArray(gl.createVertexArray());

    const locations = new Map<string, WebGLUniformLocation | null>();
    const loc = (name: string): WebGLUniformLocation | null => {
        if (!locations.has(name)) {
            locations.set(name, gl.getUniformLocation(program, name));
        }

        return locations.get(name) ?? null;
    };

    // ---- CONFIG → uniforms (once) -----------------------------------------------

    for (const [name, hex] of Object.entries(paletteOf(canvas))) {
        gl.uniform3f(loc(name), ...hexToVec3(hex));
    }

    for (const key of FLOAT_KEYS) {
        gl.uniform1f(
            loc(`u${key.charAt(0).toUpperCase()}${key.slice(1)}`),
            CONFIG[key],
        );
    }

    const stir = stirPreset();
    gl.uniform1f(loc('uStirRadius'), stir.radius);
    gl.uniform1f(loc('uStirStrength'), stir.strength);
    gl.uniform1f(loc('uStirSwirl'), stir.swirl);

    // ---- Size: from the element, coalesced to one resize per frame -------------------

    const resize = (): void => {
        const dpr = Math.min(window.devicePixelRatio || 1, CONFIG.maxDpr);
        const width = Math.max(1, Math.round(canvas.clientWidth * dpr));
        const height = Math.max(1, Math.round(canvas.clientHeight * dpr));

        if (canvas.width !== width || canvas.height !== height) {
            canvas.width = width;
            canvas.height = height;
        }

        gl.viewport(0, 0, width, height);
        gl.uniform2f(loc('iResolution'), width, height);
    };

    let resizeQueued = false;

    new ResizeObserver(() => {
        if (resizeQueued) {
            return;
        }

        resizeQueued = true;
        requestAnimationFrame(() => {
            resizeQueued = false;
            resize();
            // Keep a paused (off-screen / reduced-motion) canvas filled.
            gl.drawArrays(gl.TRIANGLES, 0, 3);
        });
    }).observe(canvas);

    resize();

    // ---- Pointer: the target only; the followers move in the loop -----------------

    const mouse = { x: 0, y: 0, ax: 0, ay: 0, tx: 0, ty: 0 };
    const rest = { x: 0, y: 0 };
    // The stir point and its (smoothed, decaying) velocity.
    const swirl = { x: 0, y: 0, vx: 0, vy: 0, placed: false };

    const aim = (event: PointerEvent): void => {
        const box = canvas.getBoundingClientRect();
        const aspect = box.width / Math.max(1, box.height);

        const x = ((event.clientX - box.left) / box.width - 0.5) * aspect;
        const y = 0.5 - (event.clientY - box.top) / box.height;

        // The first position, or a jump (the cursor coming back in somewhere
        // else), moves the stir point there without stirring.
        if (
            !swirl.placed ||
            Math.hypot(x - mouse.tx, y - mouse.ty) > STIR.jump
        ) {
            swirl.placed = true;
            swirl.x = x;
            swirl.y = y;
        }

        mouse.tx = x;
        mouse.ty = y;
    };

    window.addEventListener('pointermove', aim, { passive: true });
    window.addEventListener('pointerdown', aim, { passive: true });

    // ---- Render loop ------------------------------------------------------------------

    let clock = 0;

    const draw = (): void => {
        gl.uniform1f(loc('iTime'), clock);
        gl.uniform2f(loc('iMouse'), mouse.x, mouse.y);
        gl.uniform2f(loc('uStirPos'), swirl.x, swirl.y);
        gl.uniform2f(loc('uStirVel'), swirl.vx, swirl.vy);
        gl.drawArrays(gl.TRIANGLES, 0, 3);
    };

    // Reduced motion: one still frame, some way into the drift.
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        clock = 12;
        draw();

        return;
    }

    let visible = true;

    // Seen through: a canvas fixed behind the page is always "on screen", so
    // it watches the sections it shows through instead ([data-backdrop-area]);
    // otherwise the canvas itself.
    const areas = Array.from(document.querySelectorAll('[data-backdrop-area]'));
    const watched =
        canvas.closest('[data-intro-backdrop]') && areas.length > 0
            ? areas
            : [canvas];
    const showing = new Set<Element>();

    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    showing.add(entry.target);
                } else {
                    showing.delete(entry.target);
                }
            }

            visible = showing.size > 0;
        },
        { threshold: 0 },
    );

    watched.forEach((element) => observer.observe(element));

    let previous = performance.now();

    const frame = (now: number): void => {
        requestAnimationFrame(frame);

        const raw = now - previous;
        previous = now;

        if (!visible || document.hidden) {
            return;
        }

        // Interval pinned to [4.17, 50] ms; `step` is that in 60 Hz frames.
        const ms = raw > 50 ? 50 : raw < 4.167 ? 4.167 : raw;
        const step = ms > 36.7 ? 2.2 : ms * 0.06;
        clock += ms * 0.001;

        if (!CONFIG.cursor) {
            mouse.tx = rest.x;
            mouse.ty = rest.y;
        }

        // Two poles: a quick lead feeding a slower body.
        const lead = 0.105 * step;
        const body = 0.043 * step;
        mouse.ax += (mouse.tx - mouse.ax) * lead;
        mouse.ay += (mouse.ty - mouse.ay) * lead;
        mouse.x += (mouse.ax - mouse.x) * body;
        mouse.y += (mouse.ay - mouse.y) * body;

        // Stir: a quick follower of the cursor; its speed (per 60 Hz frame,
        // capped) is eased into the velocity, which settles once it stops.
        const follow = Math.min(1, STIR.follow * step);
        const dx = (mouse.tx - swirl.x) * follow;
        const dy = (mouse.ty - swirl.y) * follow;
        let vx = dx / step;
        let vy = dy / step;
        const speed = Math.hypot(vx, vy);

        if (speed > STIR.maxSpeed) {
            vx *= STIR.maxSpeed / speed;
            vy *= STIR.maxSpeed / speed;
        }

        swirl.x += dx;
        swirl.y += dy;

        const fade = Math.min(1, STIR.fade * step);
        swirl.vx += (vx - swirl.vx) * fade;
        swirl.vy += (vy - swirl.vy) * fade;

        draw();
    };

    draw(); // one frame before the loop, so the canvas is never blank
    requestAnimationFrame(frame);
}

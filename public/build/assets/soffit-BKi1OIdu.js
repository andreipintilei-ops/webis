var e={bgColor:`#01225e`,colorA:`#2b7be8`,colorB:`#2bc8e8`,colorC:`#4fe0d8`,colorD:`#7fe8a0`,scale:1,speed:.33,tilt:1.87,rock:.12,horizon:.36,breathe:.29,spread:.41,curve:3.32,direct:.97,bounce:.38,bounceCurve:4.25,spillCentre:.3,spillWidth:2.18,spillFloor:.26,amount:.2,warp:2.58,warpScale:.78,flow:.475,roughness:.29,lacunarity:1.99,motes:.074,moteScale:7,ambient:.24,contrast:2.45,midpoint:.57,sink:.24,glow:.38,grain:.034,grainAnim:0,dither:.58,vignette:.21,steer:-.13,lift:.11,sweep:.5,cursor:1,parallax:.0137,maxDpr:1,renderScale:1,maxFps:30},t=`#version 300 es
void main() {
  vec2 p = vec2((gl_VertexID << 1) & 2, gl_VertexID & 2);
  gl_Position = vec4(p * 2.0 - 1.0, 0.0, 1.0);
}`,n=`#version 300 es
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
}`,r={off:{radius:.3,strength:0,swirl:0},soft:{radius:.22,strength:1.5,swirl:6},medium:{radius:.3,strength:3,swirl:12},strong:{radius:.4,strength:5,swirl:20}},i={preset:`medium`,follow:.3,fade:.07,maxSpeed:.06,jump:.25};function a(){let e=new URLSearchParams(window.location.search).get(`stir`);return e&&e in r?r[e]:r[i.preset]}var o=`scale.speed.tilt.rock.horizon.breathe.spread.curve.direct.bounce.bounceCurve.spillCentre.spillWidth.spillFloor.amount.warp.warpScale.flow.roughness.lacunarity.motes.moteScale.ambient.contrast.midpoint.sink.glow.grain.grainAnim.dither.vignette.steer.lift.sweep.parallax`.split(`.`),s={blue:{uBg:e.bgColor,uColorA:e.colorA,uColorB:e.colorB,uColorC:e.colorC,uColorD:e.colorD},violet:{uBg:`#1f2868`,uColorA:`#775afc`,uColorB:`#a44cee`,uColorC:`#c98bf5`,uColorD:`#efdcff`}};function c(e){return e.dataset.palette===`violet`?s.violet:s.blue}function l(e){let t=parseInt(e.slice(1),16);return[(t>>16&255)/255,(t>>8&255)/255,(t&255)/255]}function u(){for(let e of document.querySelectorAll(`canvas[data-soffit]`))f(e)}function d(e,t){let n=e.getExtension(`KHR_parallel_shader_compile`);return n?new Promise(r=>{let i=()=>{e.getProgramParameter(t,n.COMPLETION_STATUS_KHR)?r():requestAnimationFrame(i)};i()}):Promise.resolve()}async function f(r){r.style.opacity=`0`;let s=r.getContext(`webgl2`,{alpha:!1,antialias:!1,depth:!1,stencil:!1,powerPreference:`high-performance`});if(!s)return;let u=(e,t)=>{let n=s.createShader(e);if(!n)throw Error(`[soffit] Could not create a shader.`);return s.shaderSource(n,t),s.compileShader(n),n},f=s.createProgram(),p=[u(s.VERTEX_SHADER,t),u(s.FRAGMENT_SHADER,n)];if(p.forEach(e=>s.attachShader(f,e)),s.linkProgram(f),await d(s,f),!s.getProgramParameter(f,s.LINK_STATUS))throw Error(`[soffit] ${p.map(e=>s.getShaderInfoLog(e)??``).join(` `)} ${s.getProgramInfoLog(f)??``}`);s.useProgram(f),s.bindVertexArray(s.createVertexArray());let m=new Map,h=e=>(m.has(e)||m.set(e,s.getUniformLocation(f,e)),m.get(e)??null);for(let[e,t]of Object.entries(c(r)))s.uniform3f(h(e),...l(t));for(let t of o)s.uniform1f(h(`u${t.charAt(0).toUpperCase()}${t.slice(1)}`),e[t]);let g=a();s.uniform1f(h(`uStirRadius`),g.radius),s.uniform1f(h(`uStirStrength`),g.strength),s.uniform1f(h(`uStirSwirl`),g.swirl);let _=()=>{let t=Math.min(window.devicePixelRatio||1,e.maxDpr)*e.renderScale,n=Math.max(1,Math.round(r.clientWidth*t)),i=Math.max(1,Math.round(r.clientHeight*t));(r.width!==n||r.height!==i)&&(r.width=n,r.height=i),s.viewport(0,0,n,i),s.uniform2f(h(`iResolution`),n,i)},v=!1;new ResizeObserver(()=>{v||(v=!0,requestAnimationFrame(()=>{v=!1,_(),s.drawArrays(s.TRIANGLES,0,3)}))}).observe(r),_();let y={x:0,y:0,ax:0,ay:0,tx:0,ty:0},b={x:0,y:0},x={x:0,y:0,vx:0,vy:0,placed:!1},S={x:0,y:0,known:!1},C=e=>{S.x=e.clientX,S.y=e.clientY,S.known=!0,w()},w=()=>{let e=r.getBoundingClientRect(),t=e.width/Math.max(1,e.height),n=Math.min(Math.max(((S.x-e.left)/e.width-.5)*t,-t),t),a=Math.min(Math.max(.5-(S.y-e.top)/e.height,-1),1);(!x.placed||Math.hypot(n-y.tx,a-y.ty)>i.jump)&&(x.placed=!0,x.x=n,x.y=a),y.tx=n,y.ty=a};window.addEventListener(`pointermove`,C,{passive:!0}),window.addEventListener(`pointerdown`,C,{passive:!0});let T=0,E=()=>{s.uniform1f(h(`iTime`),T),s.uniform2f(h(`iMouse`),y.x,y.y),s.uniform2f(h(`uStirPos`),x.x,x.y),s.uniform2f(h(`uStirVel`),x.vx,x.vy),s.drawArrays(s.TRIANGLES,0,3),r.style.opacity=``};if(window.matchMedia(`(prefers-reduced-motion: reduce)`).matches){T=12,E();return}let D=!0,O=Array.from(document.querySelectorAll(`[data-backdrop-area]`)),k=r.closest(`[data-intro-backdrop]`)&&O.length>0?O:[r],A=new Set,j=new IntersectionObserver(e=>{for(let t of e)t.isIntersecting?A.add(t.target):A.delete(t.target);D=A.size>0},{threshold:0});k.forEach(e=>j.observe(e));let M=performance.now(),N=1e3/e.maxFps-2,P=t=>{requestAnimationFrame(P);let n=t-M;if(n<N||(M=t,!D||document.hidden))return;let r=n>50?50:n<4.167?4.167:n,a=r>36.7?2.2:r*.06;T+=r*.001,e.cursor?S.known&&w():(y.tx=b.x,y.ty=b.y);let o=.105*a,s=.043*a;y.ax+=(y.tx-y.ax)*o,y.ay+=(y.ty-y.ay)*o,y.x+=(y.ax-y.x)*s,y.y+=(y.ay-y.y)*s;let c=Math.min(1,i.follow*a),l=(y.tx-x.x)*c,u=(y.ty-x.y)*c,d=l/a,f=u/a,p=Math.hypot(d,f);p>i.maxSpeed&&(d*=i.maxSpeed/p,f*=i.maxSpeed/p),x.x+=l,x.y+=u;let m=Math.min(1,i.fade*a);x.vx+=(d-x.vx)*m,x.vy+=(f-x.vy)*m,E()};E(),requestAnimationFrame(P)}export{u as initSoffit};
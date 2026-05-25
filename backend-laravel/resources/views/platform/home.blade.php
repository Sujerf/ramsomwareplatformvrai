@extends('layouts.landing')
@section('title', 'RansomShield — Plateforme anti-ransomware')
@section('content')
<style>
/* ════════════════════════════════════════════
   KEYFRAMES
════════════════════════════════════════════ */
@keyframes fadeUp   { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
@keyframes fadeIn   { from{opacity:0} to{opacity:1} }
@keyframes floatY   { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-14px)} }
@keyframes radarRot { to{transform:rotate(360deg)} }
@keyframes nodePing {
    0%{transform:scale(.8);opacity:.9}
    100%{transform:scale(2.6);opacity:0}
}
@keyframes pulseDot {
    0%,100%{transform:scale(.88);opacity:.8}
    50%{transform:scale(1.15);opacity:1}
}
@keyframes barGrow  { from{width:0} to{width:var(--w)} }
@keyframes marquee  { from{transform:translateX(0)} to{transform:translateX(-50%)} }
@keyframes blink    { 0%,100%{opacity:1} 50%{opacity:0} }
@keyframes ringIn   {
    0%  {transform:translate(-50%,-50%) scale(.6);opacity:0}
    60% {opacity:.9}
    100%{transform:translate(-50%,-50%) scale(1);opacity:.4}
}
@keyframes connDash { to{stroke-dashoffset:-24} }
@keyframes floatCard{
    0%,100%{transform:translateY(0) rotate(var(--r,0deg))}
    50%{transform:translateY(-8px) rotate(var(--r,0deg))}
}
@keyframes gradShift {
    0%  { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100%{ background-position: 0% 50%; }
}
@keyframes shimmerBar {
    from{background-position:-300px 0}
    to{background-position:300px 0}
}

/* ════════════════════════════════════════════
   DOT GRID OVERLAY
════════════════════════════════════════════ */
.dot-grid {
    position:fixed;inset:0;z-index:0;pointer-events:none;
    background-image:radial-gradient(circle,rgba(148,163,184,.09) 1px,transparent 1px);
    background-size:28px 28px;
}

/* ════════════════════════════════════════════
   ORB BACKGROUND
════════════════════════════════════════════ */
.orb-wrap{position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none;}
.orb{position:absolute;border-radius:50%;filter:blur(28px);opacity:.22;}
.orb-1{width:480px;height:480px;left:-140px;top:0;
        background:radial-gradient(circle,var(--accent),transparent 70%);
        animation:floatY 20s ease-in-out infinite;}
.orb-2{width:360px;height:360px;right:-100px;top:20%;
        background:radial-gradient(circle,var(--accent-2),transparent 70%);
        animation:floatY 16s ease-in-out 4s infinite;}
.orb-3{width:420px;height:420px;left:36%;bottom:-180px;
        background:radial-gradient(circle,var(--accent),transparent 70%);
        animation:floatY 22s ease-in-out 8s infinite;opacity:.14;}

/* ════════════════════════════════════════════
   MARQUEE / TICKER
════════════════════════════════════════════ */
.ticker-belt {
    position:relative;z-index:3;overflow:hidden;
    border-top:1px solid var(--border-soft);border-bottom:1px solid var(--border-soft);
    background:color-mix(in srgb,var(--bg-panel) 55%,transparent);
    backdrop-filter:blur(18px);padding:10px 0;
}
.ticker-track{display:flex;width:max-content;animation:marquee 32s linear infinite;}
.ticker-track:hover{animation-play-state:paused;}
.t-item{
    display:inline-flex;align-items:center;gap:10px;
    padding:0 36px;white-space:nowrap;
    color:var(--text-muted);font-size:12.5px;font-weight:800;
    border-right:1px solid var(--border-soft);letter-spacing:.02em;
}
.t-item i{font-size:10px;}
.t-item .a1{color:var(--accent);}
.t-item .a2{color:var(--accent-2);}

/* ════════════════════════════════════════════
   WRAP UTIL
════════════════════════════════════════════ */
.w{width:min(1180px,calc(100% - 40px));margin:0 auto;position:relative;z-index:2;}

/* ════════════════════════════════════════════
   HERO
════════════════════════════════════════════ */
.hero-section{
    position:relative;z-index:2;
    min-height:calc(100vh - 96px);
    display:grid;grid-template-columns:1fr 1fr;
    align-items:center;gap:56px;
    padding:60px 0 80px;
}

/* Left copy */
.hero-left{animation:fadeUp .9s ease both;}

.h-badge{
    display:inline-flex;align-items:center;gap:10px;
    padding:8px 14px;border-radius:999px;
    background:color-mix(in srgb,var(--accent) 12%,transparent);
    border:1px solid color-mix(in srgb,var(--accent) 26%,transparent);
    font-size:11.5px;font-weight:900;text-transform:uppercase;letter-spacing:.09em;
    margin-bottom:28px;
    color:var(--accent);
}
.h-badge-dot{
    width:8px;height:8px;border-radius:50%;
    background:var(--accent-2);
    box-shadow:0 0 0 5px color-mix(in srgb,var(--accent-2) 18%,transparent);
    animation:pulseDot 1.9s ease-in-out infinite;
}

.hero-title{
    margin:0;
    font-size:clamp(58px,9vw,108px);
    line-height:.84;
    letter-spacing:-.07em;
    font-weight:950;
    /* Évite que le premier/dernier glyphe soit rogné par le conteneur */
    padding-inline: 0.04em;
}
.hero-title .line-muted{color:var(--text-muted);}
.hero-title .line-grad{
    display:inline-block;
    background:linear-gradient(135deg,var(--accent) 0%,color-mix(in srgb,var(--accent) 50%,var(--accent-2)) 50%,var(--accent-2) 100%);
    background-size:200% 200%;
    -webkit-background-clip:text;background-clip:text;color:transparent;
    animation:gradShift 5s ease infinite;
}

.hero-sub{
    margin:26px 0 0;max-width:580px;
    color:var(--text-muted);line-height:1.82;font-size:16.5px;
}

.h-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:34px;}

/* Buttons */
.btn{
    display:inline-flex;align-items:center;justify-content:center;gap:9px;
    min-height:48px;padding:0 22px;border-radius:999px;
    font-weight:900;font-size:14.5px;border:1px solid var(--border-soft);
    transition:transform .22s ease,box-shadow .22s ease,background .22s ease;
    white-space:nowrap;cursor:pointer;text-decoration:none;
}
.btn:hover{transform:translateY(-2px);}
.btn-primary{
    background:var(--accent);color:var(--accent-contrast);
    border-color:color-mix(in srgb,var(--accent) 50%,transparent);
    box-shadow:0 8px 28px color-mix(in srgb,var(--accent) 30%,transparent);
}
.btn-primary:hover{box-shadow:0 14px 44px color-mix(in srgb,var(--accent) 42%,transparent);}
.btn-ghost{
    background:var(--bg-panel);color:var(--text-main);
    backdrop-filter:blur(20px);
}

/* Chips */
.chip-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:24px;}
.chip{
    display:inline-flex;align-items:center;gap:7px;
    padding:6px 12px;border-radius:999px;
    border:1px solid var(--border-soft);background:var(--bg-card);
    color:var(--text-muted);font-weight:800;font-size:12px;
    backdrop-filter:blur(16px);
}
.chip i{font-size:10px;color:var(--accent);}
.chip .g2{color:var(--accent-2);}

/* ════════════════════════════════════════════
   RADAR VISUALIZATION
════════════════════════════════════════════ */
.hero-right{
    position:relative;
    animation:fadeUp 1s ease .15s both;
}

.radar-panel{
    position:relative;
    border:1px solid var(--border-soft);
    border-radius:40px;
    background:var(--bg-panel);
    box-shadow:var(--shadow-soft),0 0 0 1px color-mix(in srgb,var(--accent) 8%,transparent);
    padding:32px;
    backdrop-filter:blur(30px);
    overflow:hidden;
}
/* gradient border glow top */
.radar-panel::before{
    content:"";position:absolute;inset:0;border-radius:inherit;
    background:linear-gradient(135deg,
        color-mix(in srgb,var(--accent) 20%,transparent) 0%,
        transparent 50%,
        color-mix(in srgb,var(--accent-2) 14%,transparent) 100%
    );
    pointer-events:none;z-index:0;
}
/* Corner glow */
.radar-panel::after{
    content:"";position:absolute;top:-80px;right:-80px;
    width:260px;height:260px;border-radius:50%;
    background:color-mix(in srgb,var(--accent) 18%,transparent);
    filter:blur(4px);z-index:0;
}

.radar-inner{position:relative;z-index:1;}

/* Panel header */
.rp-header{
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:24px;
}
.rp-title{font-size:13px;font-weight:900;color:var(--text-muted);letter-spacing:.05em;text-transform:uppercase;}
.rp-live{
    display:inline-flex;align-items:center;gap:7px;
    padding:5px 11px;border-radius:999px;
    background:color-mix(in srgb,var(--accent-2) 14%,transparent);
    border:1px solid color-mix(in srgb,var(--accent-2) 24%,transparent);
    font-size:11px;font-weight:900;color:var(--accent-2);
}
.rp-live::before{
    content:"";width:7px;height:7px;border-radius:50%;
    background:var(--accent-2);
    animation:pulseDot 1.5s ease-in-out infinite;
}

/* Radar canvas */
.radar-canvas{
    position:relative;
    width:300px;height:300px;
    margin:0 auto;
}

/* Rings */
.r-ring{
    position:absolute;border-radius:50%;
    border:1px solid color-mix(in srgb,var(--accent) 18%,transparent);
    top:50%;left:50%;
    transform:translate(-50%,-50%);
    animation:ringIn 2s ease both;
}
.r-ring:nth-child(1){width:100%;height:100%;animation-delay:.0s;}
.r-ring:nth-child(2){width:72%;height:72%;animation-delay:.12s;
    border-color:color-mix(in srgb,var(--accent) 26%,transparent);}
.r-ring:nth-child(3){width:46%;height:46%;animation-delay:.24s;
    border-color:color-mix(in srgb,var(--accent) 34%,transparent);}
.r-ring:nth-child(4){width:22%;height:22%;animation-delay:.36s;
    border-color:color-mix(in srgb,var(--accent) 44%,transparent);}

/* Cross hairs */
.r-cross{
    position:absolute;top:50%;left:50%;
    transform:translate(-50%,-50%);
    width:100%;height:100%;
    pointer-events:none;
}
.r-cross::before,.r-cross::after{
    content:"";position:absolute;
    background:color-mix(in srgb,var(--accent) 12%,transparent);
}
.r-cross::before{width:100%;height:1px;top:50%;left:0;transform:translateY(-50%);}
.r-cross::after{width:1px;height:100%;top:0;left:50%;transform:translateX(-50%);}

/* Sweep */
.r-sweep{
    position:absolute;width:100%;height:100%;
    border-radius:50%;
    background:conic-gradient(
        from 0deg,
        transparent 0deg,
        color-mix(in srgb,var(--accent) 5%,transparent) 40deg,
        color-mix(in srgb,var(--accent) 16%,transparent) 65deg,
        color-mix(in srgb,var(--accent) 4%,transparent) 80deg,
        transparent 90deg
    );
    animation:radarRot 3.8s linear infinite;
}

/* Center shield */
.r-center{
    position:absolute;top:50%;left:50%;
    transform:translate(-50%,-50%);
    width:62px;height:62px;border-radius:22px;
    display:grid;place-items:center;
    background:linear-gradient(135deg,var(--accent),var(--accent-2));
    color:var(--accent-contrast);font-size:24px;
    box-shadow:
        0 0 0 8px color-mix(in srgb,var(--accent) 14%,transparent),
        0 0 40px color-mix(in srgb,var(--accent) 36%,transparent),
        0 0 80px color-mix(in srgb,var(--accent) 16%,transparent);
    z-index:4;
}

/* Threat nodes */
.t-node{
    position:absolute;z-index:3;
    display:flex;flex-direction:column;align-items:center;gap:5px;
}
.node-dot{
    width:14px;height:14px;border-radius:50%;
    background:var(--danger);
    box-shadow:0 0 0 4px color-mix(in srgb,var(--danger) 22%,transparent);
    position:relative;
}
.node-dot.ok{background:var(--accent-2);box-shadow:0 0 0 4px color-mix(in srgb,var(--accent-2) 22%,transparent);}
.node-ping{
    position:absolute;inset:-7px;border-radius:50%;
    border:1.5px solid var(--danger);opacity:0;
    animation:nodePing 2.2s ease-out infinite;
}
.node-ping.ok{border-color:var(--accent-2);}
.node-ping.d2{animation-delay:.6s;}
.node-ping.d3{animation-delay:1.1s;}
.node-tag{
    background:var(--bg-card);border:1px solid var(--border-soft);
    border-radius:999px;padding:3px 8px;
    font-size:10px;font-weight:900;color:var(--text-muted);
    white-space:nowrap;
}
/* Node positions */
.tn-1{top:9%;right:12%;}
.tn-2{bottom:16%;right:8%;}
.tn-3{bottom:8%;left:16%;}
.tn-4{top:22%;left:6%;}

/* SVG connector lines */
.r-lines{
    position:absolute;inset:0;width:100%;height:100%;
    pointer-events:none;z-index:2;overflow:visible;
}
.conn-line{
    stroke:color-mix(in srgb,var(--accent) 30%,transparent);
    stroke-width:1;fill:none;
    stroke-dasharray:5 5;
    animation:connDash 1.4s linear infinite;
}
.conn-line.red{stroke:color-mix(in srgb,var(--danger) 40%,transparent);}
.conn-line.green{stroke:color-mix(in srgb,var(--accent-2) 35%,transparent);}

/* Floating metric cards */
.float-cards{position:absolute;inset:0;pointer-events:none;z-index:5;}
.fc{
    position:absolute;
    padding:12px 16px;border-radius:18px;
    background:var(--bg-panel);border:1px solid var(--border-soft);
    backdrop-filter:blur(22px);box-shadow:var(--shadow-soft);
    pointer-events:auto;
    animation:floatCard 6s ease-in-out infinite;
}
.fc-1{top:-20px;left:-24px;--r:-3deg;animation-delay:.5s;}
.fc-2{bottom:-18px;right:-20px;--r:2deg;animation-delay:1.5s;}
.fc-3{top:42%;right:-28px;--r:1deg;animation-delay:3s;}

.fc-num{font-size:28px;font-weight:950;letter-spacing:-.05em;}
.fc-num.red{color:var(--danger);}
.fc-num.blue{color:var(--accent);}
.fc-num.grn{color:var(--accent-2);}
.fc-lbl{font-size:10.5px;font-weight:800;color:var(--text-muted);margin-top:3px;}
.fc-bar{height:5px;border-radius:999px;background:color-mix(in srgb,var(--text-muted) 15%,transparent);margin-top:8px;overflow:hidden;}
.fc-bfill{height:100%;border-radius:inherit;--w:74%;
    background:linear-gradient(90deg,var(--accent),var(--danger));
    animation:barGrow 1.8s ease .8s both,
               shimmerBar 2s linear infinite;
    background-size:300% 100%;
}
.fc-row{display:flex;align-items:center;justify-content:space-between;gap:12px;}
.fc-tag{
    padding:3px 8px;border-radius:999px;font-size:10px;font-weight:900;
    background:color-mix(in srgb,var(--danger) 14%,transparent);
    border:1px solid color-mix(in srgb,var(--danger) 22%,transparent);
    color:#fca5a5;
}
.fc-tag.ok{
    background:color-mix(in srgb,var(--accent-2) 14%,transparent);
    border-color:color-mix(in srgb,var(--accent-2) 22%,transparent);
    color:var(--accent-2);
}

/* Below radar: mini metric strip */
.radar-metrics{
    display:grid;grid-template-columns:repeat(3,1fr);
    gap:10px;margin-top:20px;
}
.rm{
    padding:12px;border-radius:16px;text-align:center;
    background:color-mix(in srgb,var(--bg-main) 60%,transparent);
    border:1px solid var(--border-soft);
}
.rm-val{font-size:20px;font-weight:950;letter-spacing:-.04em;}
.rm-val.a{color:var(--accent);}
.rm-val.d{color:var(--danger);}
.rm-val.g{color:var(--accent-2);}
.rm-lbl{font-size:10px;font-weight:800;color:var(--text-muted);margin-top:3px;}

/* ════════════════════════════════════════════
   STATS BAND
════════════════════════════════════════════ */
.stats-band{
    position:relative;z-index:2;
    border-top:1px solid var(--border-soft);
    border-bottom:1px solid var(--border-soft);
    background:color-mix(in srgb,var(--bg-panel) 60%,transparent);
    backdrop-filter:blur(20px);
}
.stats-inner{
    display:grid;grid-template-columns:repeat(4,1fr);
    width:min(1180px,calc(100% - 40px));margin:0 auto;
}
.s-item{
    padding:32px 24px;text-align:center;
    border-right:1px solid var(--border-soft);
    position:relative;
}
.s-item:last-child{border-right:none;}
.s-num{
    font-size:52px;font-weight:950;letter-spacing:-.07em;
    background:linear-gradient(135deg,var(--text-main) 40%,var(--accent));
    -webkit-background-clip:text;background-clip:text;color:transparent;
    line-height:1;
}
.s-label{color:var(--text-muted);font-size:13px;font-weight:800;margin-top:8px;letter-spacing:.01em;}
.s-icon{
    position:absolute;top:50%;right:24px;transform:translateY(-50%);
    font-size:32px;opacity:.07;color:var(--accent);
}

/* ════════════════════════════════════════════
   SECTION HEADER
════════════════════════════════════════════ */
.sec-head{text-align:center;max-width:780px;margin:0 auto 44px;}
.kicker{
    display:inline-flex;align-items:center;gap:8px;
    padding:7px 14px;border-radius:999px;margin-bottom:18px;
    background:color-mix(in srgb,var(--accent) 12%,transparent);
    border:1px solid color-mix(in srgb,var(--accent) 24%,transparent);
    color:var(--accent);font-size:11.5px;font-weight:900;
    text-transform:uppercase;letter-spacing:.09em;
}
.sec-head h2{
    margin:0;font-size:clamp(36px,5.5vw,64px);
    line-height:.90;letter-spacing:-.07em;
}
.sec-head p{
    color:var(--text-muted);line-height:1.82;
    margin:18px auto 0;max-width:660px;font-size:16px;
}

/* ════════════════════════════════════════════
   BENTO GRID (FEATURES)
════════════════════════════════════════════ */
.bento-section{padding:90px 0 70px;}
.bento{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    grid-template-rows:auto;
    gap:16px;
    margin-top:44px;
}
.bc{
    border-radius:30px;padding:28px;
    border:1px solid var(--border-soft);
    background:var(--bg-panel);
    box-shadow:var(--shadow-soft);
    backdrop-filter:blur(22px);
    position:relative;overflow:hidden;
    transition:transform .25s ease,box-shadow .25s ease,border-color .25s ease;
}
.bc:hover{
    transform:translateY(-5px);
    border-color:color-mix(in srgb,var(--accent) 30%,transparent);
    box-shadow:var(--shadow-soft),0 0 40px color-mix(in srgb,var(--accent) 12%,transparent);
}
.bc.green:hover{border-color:color-mix(in srgb,var(--accent-2) 30%,transparent);}
.bc.danger:hover{border-color:color-mix(in srgb,var(--danger) 24%,transparent);}

/* Grid spans */
.bc-1{grid-column:span 4;}
.bc-2{grid-column:span 2;}
.bc-3{grid-column:span 2;}
.bc-4{grid-column:span 2;}
.bc-5{grid-column:span 2;}

/* Glow orb in card */
.bc::after{
    content:"";position:absolute;
    width:180px;height:180px;border-radius:50%;
    background:color-mix(in srgb,var(--accent) 10%,transparent);
    right:-70px;bottom:-70px;pointer-events:none;
}
.bc.green::after{background:color-mix(in srgb,var(--accent-2) 10%,transparent);}
.bc.danger::after{background:color-mix(in srgb,var(--danger) 8%,transparent);}

/* Card icon */
.bc-icon{
    width:52px;height:52px;border-radius:18px;
    display:grid;place-items:center;font-size:22px;
    background:color-mix(in srgb,var(--accent) 15%,transparent);
    border:1px solid color-mix(in srgb,var(--accent) 25%,transparent);
    color:var(--accent);margin-bottom:20px;position:relative;z-index:1;
}
.bc.green .bc-icon{
    background:color-mix(in srgb,var(--accent-2) 15%,transparent);
    border-color:color-mix(in srgb,var(--accent-2) 25%,transparent);
    color:var(--accent-2);
}
.bc.danger .bc-icon{
    background:color-mix(in srgb,var(--danger) 12%,transparent);
    border-color:color-mix(in srgb,var(--danger) 20%,transparent);
    color:var(--danger);
}
.bc h3{margin:0;font-size:20px;letter-spacing:-.04em;position:relative;z-index:1;}
.bc p{color:var(--text-muted);line-height:1.78;margin-top:12px;font-size:14px;position:relative;z-index:1;}

/* Check list */
.bc-list{display:grid;gap:10px;margin-top:18px;position:relative;z-index:1;}
.bc-item{display:flex;gap:10px;align-items:flex-start;color:var(--text-muted);font-size:13.5px;line-height:1.5;}
.bc-check{
    flex:0 0 auto;width:20px;height:20px;border-radius:50%;
    display:grid;place-items:center;font-size:9px;
    background:color-mix(in srgb,var(--accent-2) 16%,transparent);
    color:var(--accent-2);margin-top:2px;
}
.bc.green .bc-check{background:color-mix(in srgb,var(--accent) 16%,transparent);color:var(--accent);}
.bc.danger .bc-check{background:color-mix(in srgb,var(--danger) 14%,transparent);color:var(--danger);}

/* Mini visualization inside big card */
.bc-viz{
    margin-top:22px;padding:16px;border-radius:20px;
    background:color-mix(in srgb,var(--bg-main) 65%,transparent);
    border:1px solid var(--border-soft);
    position:relative;z-index:1;
}
.viz-bar-row{display:flex;align-items:center;gap:12px;margin-bottom:10px;}
.viz-bar-row:last-child{margin-bottom:0;}
.viz-label{font-size:11px;font-weight:800;color:var(--text-muted);width:80px;flex-shrink:0;}
.viz-bar{flex:1;height:8px;border-radius:999px;background:color-mix(in srgb,var(--text-muted) 14%,transparent);overflow:hidden;}
.viz-fill{height:100%;border-radius:inherit;animation:barGrow 1.5s ease both;}
.viz-fill.r1{--w:72%;background:linear-gradient(90deg,var(--accent),var(--danger));animation-delay:.4s;}
.viz-fill.r2{--w:44%;background:linear-gradient(90deg,var(--accent),color-mix(in srgb,var(--accent) 60%,var(--accent-2)));animation-delay:.6s;}
.viz-fill.r3{--w:18%;background:var(--accent-2);animation-delay:.8s;}
.viz-pct{font-size:11px;font-weight:900;color:var(--text-muted);width:32px;text-align:right;flex-shrink:0;}

/* Timeline mini viz */
.mini-timeline{display:flex;flex-direction:column;gap:8px;margin-top:18px;position:relative;z-index:1;}
.mt-row{
    display:flex;align-items:center;gap:12px;
    padding:10px 12px;border-radius:14px;
    background:color-mix(in srgb,var(--bg-main) 60%,transparent);
    border:1px solid var(--border-soft);
    font-size:12px;
}
.mt-dot{
    width:10px;height:10px;border-radius:50%;flex-shrink:0;
    background:var(--text-muted);
}
.mt-dot.r{background:var(--danger);}
.mt-dot.b{background:var(--accent);}
.mt-dot.g{background:var(--accent-2);}
.mt-label{font-weight:900;color:var(--text-main);flex:1;}
.mt-time{color:var(--text-muted);font-size:10px;font-weight:800;}

/* ════════════════════════════════════════════
   PROCESS FLOW
════════════════════════════════════════════ */
.flow-section{padding:20px 0 90px;}
.flow-row{
    display:flex;align-items:flex-start;gap:0;
    margin-top:44px;position:relative;
}
/* Connecting line */
.flow-row::before{
    content:"";position:absolute;
    top:38px;left:10%;right:10%;height:1px;
    background:linear-gradient(90deg,
        transparent,
        color-mix(in srgb,var(--accent) 20%,transparent) 20%,
        var(--accent) 50%,
        color-mix(in srgb,var(--accent-2) 20%,transparent) 80%,
        transparent
    );
}
.flow-step{
    flex:1;display:flex;flex-direction:column;
    align-items:center;text-align:center;padding:0 14px;
    animation:fadeUp .8s ease both;
}
.flow-step:nth-child(2){animation-delay:.08s;}
.flow-step:nth-child(3){animation-delay:.16s;}
.flow-step:nth-child(4){animation-delay:.24s;}
.flow-step:nth-child(5){animation-delay:.32s;}

.fs-icon{
    position:relative;
    width:76px;height:76px;border-radius:26px;
    display:grid;place-items:center;font-size:28px;
    color:var(--accent);
    background:var(--bg-panel);
    border:1px solid var(--border-soft);
    box-shadow:var(--shadow-soft),0 0 0 5px var(--bg-main);
    margin-bottom:20px;z-index:1;
    transition:transform .25s ease,box-shadow .25s ease;
}
.flow-step:hover .fs-icon{
    transform:translateY(-6px);
    box-shadow:var(--shadow-soft),0 0 0 5px var(--bg-main),0 0 28px color-mix(in srgb,var(--accent) 28%,transparent);
}
.flow-step:last-child .fs-icon{color:var(--accent-2);}
.fs-num{
    position:absolute;top:-8px;right:-8px;
    width:22px;height:22px;border-radius:50%;
    background:var(--accent);color:var(--accent-contrast);
    font-size:11px;font-weight:950;display:grid;place-items:center;
}
.flow-step:last-child .fs-num{background:var(--accent-2);}
.flow-step h3{margin:0;font-size:15.5px;font-weight:900;letter-spacing:-.025em;}
.flow-step p{margin:10px 0 0;color:var(--text-muted);font-size:13px;line-height:1.68;}

/* ════════════════════════════════════════════
   CTA
════════════════════════════════════════════ */
.cta-section{padding:0 0 110px;}
.cta-box{
    position:relative;overflow:hidden;
    padding:clamp(40px,7vw,72px);border-radius:44px;text-align:center;
    background:
        linear-gradient(135deg,
            color-mix(in srgb,var(--accent) 18%,transparent),
            color-mix(in srgb,var(--accent-2) 10%,transparent)),
        var(--bg-panel);
    border:1px solid color-mix(in srgb,var(--accent) 28%,transparent);
    box-shadow:var(--shadow-soft),
               0 0 100px color-mix(in srgb,var(--accent) 12%,transparent);
}
/* Grid lines decoration */
.cta-box::before{
    content:"";position:absolute;inset:0;
    background:
        repeating-linear-gradient(0deg,
            color-mix(in srgb,var(--accent) 4%,transparent) 0,
            color-mix(in srgb,var(--accent) 4%,transparent) 1px,
            transparent 1px,transparent 40px),
        repeating-linear-gradient(90deg,
            color-mix(in srgb,var(--accent) 4%,transparent) 0,
            color-mix(in srgb,var(--accent) 4%,transparent) 1px,
            transparent 1px,transparent 40px);
    pointer-events:none;
}
/* Corner glow spots */
.cta-box::after{
    content:"";position:absolute;
    width:400px;height:400px;border-radius:50%;
    left:-120px;bottom:-200px;
    background:color-mix(in srgb,var(--accent-2) 12%,transparent);
    filter:blur(2px);
}
.cta-shield{
    position:relative;z-index:1;
    width:90px;height:90px;border-radius:32px;
    display:grid;place-items:center;font-size:38px;
    background:linear-gradient(135deg,var(--accent),var(--accent-2));
    color:var(--accent-contrast);
    margin:0 auto 32px;
    box-shadow:0 12px 50px color-mix(in srgb,var(--accent) 30%,transparent),
               0 0 0 12px color-mix(in srgb,var(--accent) 12%,transparent);
    animation:floatY 6s ease-in-out infinite;
}
.cta-box h2{
    position:relative;z-index:1;
    margin:0;font-size:clamp(40px,6vw,76px);
    line-height:.92;letter-spacing:-.065em;
    /* padding pour éviter le clip des glyphes avec overflow:hidden du parent */
    padding-inline: 0.05em;
}
.cta-box h2 em{
    font-style:normal;
    background:linear-gradient(135deg,var(--accent),var(--accent-2));
    background-size:200% 200%;
    -webkit-background-clip:text;background-clip:text;color:transparent;
    animation:gradShift 4s ease infinite;
}
.cta-box p{
    position:relative;z-index:1;
    color:var(--text-muted);line-height:1.82;
    max-width:640px;margin:20px auto 0;font-size:16px;
}
.cta-btns{
    position:relative;z-index:1;
    display:flex;flex-wrap:wrap;gap:12px;
    justify-content:center;margin-top:36px;
}

/* ════════════════════════════════════════════
   FOOTER
════════════════════════════════════════════ */
.site-footer{
    position:relative;z-index:2;
    border-top:1px solid var(--border-soft);
    background:color-mix(in srgb,var(--bg-panel) 50%,transparent);
    backdrop-filter:blur(20px);
}
.footer-inner{
    display:grid;
    grid-template-columns:1fr auto 1fr;
    align-items:center;gap:24px;
    width:min(1180px,calc(100% - 40px));margin:0 auto;
    padding:24px 0;
}
.f-brand{font-size:15px;font-weight:950;letter-spacing:-.03em;}
.f-brand span{color:var(--accent);}
.f-nav{display:flex;gap:22px;justify-content:center;}
.f-nav a{color:var(--text-muted);font-size:13px;font-weight:700;transition:color .2s;}
.f-nav a:hover{color:var(--text-main);}
.f-copy{color:var(--text-muted);font-size:12px;font-weight:600;text-align:right;}

/* ════════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════════ */
@media(max-width:1100px){
    .bento{grid-template-columns:repeat(2,1fr);}
    .bc-1,.bc-2,.bc-3,.bc-4,.bc-5{grid-column:span 1;}
    .bc-1{grid-column:span 2;}
    .stats-inner{grid-template-columns:repeat(2,1fr);}
    .s-item:nth-child(2){border-right:none;}
    .s-item:nth-child(3){border-top:1px solid var(--border-soft);}
    .s-item:nth-child(4){border-top:1px solid var(--border-soft);border-right:none;}
}
@media(max-width:960px){
    .hero-section{grid-template-columns:1fr;min-height:auto;padding-top:40px;gap:48px;}
    .radar-panel{max-width:480px;margin:0 auto;}
    .flow-row::before{display:none;}
    .flow-row{flex-wrap:wrap;gap:24px;}
    .flow-step{min-width:calc(50% - 12px);}
    .footer-inner{grid-template-columns:1fr;text-align:center;}
    .f-copy{text-align:center;}
    .f-nav{flex-wrap:wrap;}
}
@media(max-width:680px){
    .bento{grid-template-columns:1fr;}
    .bc-1{grid-column:span 1;}
    .stats-inner{grid-template-columns:repeat(2,1fr);}
    .hero-title{font-size:58px;}
    .h-actions .btn{width:100%;justify-content:center;}
    .flow-step{min-width:100%;}
    .radar-canvas{width:240px;height:240px;}
    .fc-1,.fc-2,.fc-3{display:none;}
}
</style>

{{-- ─── BG LAYERS ─────────────────────────── --}}
<div class="dot-grid" aria-hidden="true"></div>
<div class="orb-wrap" aria-hidden="true">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>

{{-- ─── TICKER ─────────────────────────────── --}}
<div class="ticker-belt" aria-hidden="true">
    <div class="ticker-track">
        @foreach(range(1,2) as $_)
        <span class="t-item"><i class="fa-solid fa-shield-halved a1"></i> Analyse comportementale temps réel</span>
        <span class="t-item"><i class="fa-solid fa-bolt a2"></i> Score de risque dynamique</span>
        <span class="t-item"><i class="fa-solid fa-network-wired a1"></i> Multi-hôtes sur réseau KVM</span>
        <span class="t-item"><i class="fa-solid fa-eye a2"></i> Watchdog Python par inotify</span>
        <span class="t-item"><i class="fa-solid fa-bell a1"></i> Alertes dédupliquées par agent</span>
        <span class="t-item"><i class="fa-solid fa-user-check a2"></i> Approbation humaine requise</span>
        <span class="t-item"><i class="fa-solid fa-clock-rotate-left a1"></i> Timeline d'incident complète</span>
        <span class="t-item"><i class="fa-solid fa-envelope a2"></i> Notifications mail + UI</span>
        @endforeach
    </div>
</div>

{{-- ─── HERO ────────────────────────────────── --}}
<section class="w hero-section">

    {{-- LEFT COPY --}}
    <div class="hero-left">
        <div class="h-badge">
            <span class="h-badge-dot"></span>
            <i class="fa-solid fa-shield-halved"></i>
            Plateforme SOC · Plateforme de Cybersécurité
        </div>

        <h1 class="hero-title">
            Stoppez<br>
            <span class="line-grad">ransomwares</span><br>
            <span class="line-muted">avant l'impact.</span>
        </h1>

        <p class="hero-sub">
            RansomShield analyse les comportements fichiers remontés par un agent Python,
            calcule un score de risque, crée alertes et incidents, puis propose des actions
            de protection contrôlées par validation humaine.
        </p>

        <div class="h-actions">
            <a href="{{ route('platform.dashboard') }}" class="btn btn-primary">
                <i class="fa-solid fa-display"></i> Console SOC
            </a>
            <a href="{{ route('platform.login') }}" class="btn btn-ghost">
                <i class="fa-solid fa-right-to-bracket"></i> Se connecter
            </a>
        </div>

        <div class="chip-row">
            <span class="chip"><i class="fa-solid fa-circle-dot"></i> Détection comportementale</span>
            <span class="chip"><i class="fa-solid fa-circle-dot g2"></i> Validation humaine</span>
            <span class="chip"><i class="fa-solid fa-circle-dot"></i> Timeline incident</span>
            <span class="chip"><i class="fa-solid fa-circle-dot g2"></i> Multi-agents</span>
        </div>
    </div>

    {{-- RIGHT: RADAR VISUALIZATION --}}
    <div class="hero-right">
        <div class="radar-panel">
            <div class="radar-inner">
                <div class="rp-header">
                    <span class="rp-title"><i class="fa-solid fa-satellite-dish" style="margin-right:7px;color:var(--accent);"></i>Threat Radar</span>
                    <span class="rp-live">LIVE</span>
                </div>

                <div class="radar-canvas">
                    {{-- Rings --}}
                    <div class="r-ring"></div>
                    <div class="r-ring"></div>
                    <div class="r-ring"></div>
                    <div class="r-ring"></div>

                    {{-- Cross --}}
                    <div class="r-cross"></div>

                    {{-- Sweep --}}
                    <div class="r-sweep"></div>

                    {{-- Center --}}
                    <div class="r-center">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    {{-- SVG connector lines --}}
                    <svg class="r-lines" viewBox="0 0 300 300">
                        {{-- from center to each node --}}
                        <line class="conn-line red"   x1="150" y1="150" x2="260" y2="44"/>
                        <line class="conn-line red"   x1="150" y1="150" x2="272" y2="222" style="animation-delay:.4s"/>
                        <line class="conn-line green" x1="150" y1="150" x2="54"  y2="272" style="animation-delay:.8s"/>
                        <line class="conn-line"       x1="150" y1="150" x2="30"  y2="80"  style="animation-delay:1.2s"/>
                    </svg>

                    {{-- Threat nodes --}}
                    <div class="t-node tn-1">
                        <div class="node-dot">
                            <div class="node-ping"></div>
                            <div class="node-ping d2"></div>
                        </div>
                        <div class="node-tag">vm-lab-72</div>
                    </div>

                    <div class="t-node tn-2">
                        <div class="node-dot">
                            <div class="node-ping d3"></div>
                        </div>
                        <div class="node-tag">vm-lab-81</div>
                    </div>

                    <div class="t-node tn-3">
                        <div class="node-dot ok">
                            <div class="node-ping ok"></div>
                        </div>
                        <div class="node-tag" style="color:var(--accent-2)">agent-sain</div>
                    </div>

                    <div class="t-node tn-4">
                        <div class="node-dot">
                            <div class="node-ping d2"></div>
                        </div>
                        <div class="node-tag">file-server</div>
                    </div>

                    {{-- Floating cards --}}
                    <div class="float-cards">
                        <div class="fc fc-1">
                            <div class="fc-row">
                                <div>
                                    <div class="fc-num red">74</div>
                                    <div class="fc-lbl">Risk Score</div>
                                </div>
                                <div class="fc-tag">critical</div>
                            </div>
                            <div class="fc-bar"><div class="fc-bfill"></div></div>
                        </div>
                        <div class="fc fc-2">
                            <div class="fc-row">
                                <div>
                                    <div class="fc-num grn">{{ $openAlertsCount }}</div>
                                    <div class="fc-lbl">Alertes actives</div>
                                </div>
                                <div class="fc-tag ok">open</div>
                            </div>
                        </div>
                        <div class="fc fc-3">
                            <div class="fc-num blue">{{ $agentsCount }}</div>
                            <div class="fc-lbl">Agents / Réseau</div>
                        </div>
                    </div>
                </div>

                {{-- Mini metrics below radar --}}
                <div class="radar-metrics">
                    <div class="rm">
                        <div class="rm-val a">{{ $agentsCount }}</div>
                        <div class="rm-lbl">Agents</div>
                    </div>
                    <div class="rm">
                        <div class="rm-val d">{{ $openAlertsCount }}</div>
                        <div class="rm-lbl">Alertes</div>
                    </div>
                    <div class="rm">
                        <div class="rm-val g">{{ $openIncidentsCount }}</div>
                        <div class="rm-lbl">Incidents</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── STATS BAND ─────────────────────────── --}}
<div class="stats-band">
    <div class="stats-inner">
        <div class="s-item">
            <i class="s-icon fa-solid fa-robot"></i>
            <div class="s-num">{{ $agentsCount }}</div>
            <div class="s-label">Agents enrôlés</div>
        </div>
        <div class="s-item">
            <i class="s-icon fa-solid fa-bell"></i>
            <div class="s-num">{{ $openAlertsCount }}</div>
            <div class="s-label">Alertes ouvertes</div>
        </div>
        <div class="s-item">
            <i class="s-icon fa-solid fa-folder-open"></i>
            <div class="s-num">{{ $openIncidentsCount }}</div>
            <div class="s-label">Incidents actifs</div>
        </div>
        <div class="s-item">
            <i class="s-icon fa-solid fa-circle-check"></i>
            <div class="s-num">{{ $pendingActionsCount }}</div>
            <div class="s-label">Actions en attente</div>
        </div>
    </div>
</div>

{{-- ─── BENTO FEATURES ──────────────────────── --}}
<section class="bento-section">
    <div class="w">
        <div class="sec-head">
            <div class="kicker"><i class="fa-solid fa-star"></i> Capacités clés</div>
            <h2>Pensée pour un vrai raisonnement SOC.</h2>
            <p>Chaque fonctionnalité est conçue pour démontrer une chaîne de sécurité crédible,
               de la collecte à la réponse, sans automatisation aveugle.</p>
        </div>

        <div class="bento">
            {{-- Big card: Detection --}}
            <article class="bc bc-1">
                <div class="bc-icon"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
                <h3>Détection comportementale orientée ransomware</h3>
                <p>Le moteur observe les comportements fichiers — extensions, volumes, chemins partagés, notes de rançon — et calcule un score de risque multi-critères.</p>
                <div class="bc-list">
                    <div class="bc-item"><span class="bc-check"><i class="fa-solid fa-check"></i></span>Règles et seuils modifiables depuis la console</div>
                    <div class="bc-item"><span class="bc-check"><i class="fa-solid fa-check"></i></span>Extensions sensibles + suspectes indépendantes</div>
                    <div class="bc-item"><span class="bc-check"><i class="fa-solid fa-check"></i></span>Score dynamique de 0 à 100 avec niveau qualifié</div>
                </div>
                <div class="bc-viz">
                    <div class="viz-bar-row">
                        <span class="viz-label">Extensions</span>
                        <div class="viz-bar"><div class="viz-fill r1"></div></div>
                        <span class="viz-pct">72%</span>
                    </div>
                    <div class="viz-bar-row">
                        <span class="viz-label">Volume</span>
                        <div class="viz-bar"><div class="viz-fill r2"></div></div>
                        <span class="viz-pct">44%</span>
                    </div>
                    <div class="viz-bar-row">
                        <span class="viz-label">Chemins</span>
                        <div class="viz-bar"><div class="viz-fill r3"></div></div>
                        <span class="viz-pct">18%</span>
                    </div>
                </div>
            </article>

            {{-- Card: Controlled Response --}}
            <article class="bc bc-2 green">
                <div class="bc-icon"><i class="fa-solid fa-hand-point-up"></i></div>
                <h3>Réponse contrôlée</h3>
                <p>Les actions sensibles ne s'exécutent jamais sans votre accord explicite.</p>
                <div class="bc-list">
                    <div class="bc-item"><span class="bc-check"><i class="fa-solid fa-check"></i></span>Isolation hôte avec approbation</div>
                    <div class="bc-item"><span class="bc-check"><i class="fa-solid fa-check"></i></span>Kill process en mode manuel</div>
                    <div class="bc-item"><span class="bc-check"><i class="fa-solid fa-check"></i></span>Historique complet décisions</div>
                </div>
            </article>

            {{-- Card: Timeline --}}
            <article class="bc bc-3 danger">
                <div class="bc-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h3>Timeline d'incident</h3>
                <p>Chaque incident dispose d'une chronologie complète exploitable en audit.</p>
                <div class="mini-timeline">
                    <div class="mt-row">
                        <div class="mt-dot r"></div>
                        <span class="mt-label">Alerte créée</span>
                        <span class="mt-time">08:42</span>
                    </div>
                    <div class="mt-row">
                        <div class="mt-dot b"></div>
                        <span class="mt-label">Incident ouvert</span>
                        <span class="mt-time">08:43</span>
                    </div>
                    <div class="mt-row">
                        <div class="mt-dot g"></div>
                        <span class="mt-label">Action approuvée</span>
                        <span class="mt-time">08:51</span>
                    </div>
                </div>
            </article>

            {{-- Card: Agents --}}
            <article class="bc bc-4">
                <div class="bc-icon"><i class="fa-solid fa-robot"></i></div>
                <h3>Agents Python</h3>
                <p>Watchdog inotify déployé sur VM par bootstrap one-liner. Enrôlement automatique, token sécurisé.</p>
                <div class="bc-list">
                    <div class="bc-item"><span class="bc-check"><i class="fa-solid fa-check"></i></span>Bootstrap via URL signée</div>
                    <div class="bc-item"><span class="bc-check"><i class="fa-solid fa-check"></i></span>Heartbeat + commandes polling</div>
                </div>
            </article>

            {{-- Card: Configuration --}}
            <article class="bc bc-5">
                <div class="bc-icon"><i class="fa-solid fa-sliders"></i></div>
                <h3>Configuration totale</h3>
                <p>Tous les paramètres de détection et réponse sont éditables depuis la console sans toucher au code.</p>
                <div class="bc-list">
                    <div class="bc-item"><span class="bc-check"><i class="fa-solid fa-check"></i></span>Seuils, règles, extensions</div>
                    <div class="bc-item"><span class="bc-check"><i class="fa-solid fa-check"></i></span>Politiques de protection</div>
                </div>
            </article>
        </div>
    </div>
</section>

{{-- ─── FLOW SECTION ────────────────────────── --}}
<section class="flow-section">
    <div class="w">
        <div class="sec-head">
            <div class="kicker"><i class="fa-solid fa-route"></i> Flux de détection</div>
            <h2>Du fichier suspect à la décision.</h2>
            <p>Une chaîne claire et traçable — observer, analyser, qualifier, alerter, décider.</p>
        </div>

        <div class="flow-row">
            <div class="flow-step">
                <div class="fs-icon"><i class="fa-solid fa-eye"></i><span class="fs-num">1</span></div>
                <h3>Collecte</h3>
                <p>L'agent Python surveille créations, modifications, renommages et extensions suspectes par inotify.</p>
            </div>
            <div class="flow-step">
                <div class="fs-icon"><i class="fa-solid fa-brain"></i><span class="fs-num">2</span></div>
                <h3>Analyse</h3>
                <p>Laravel applique règles, seuils et extensions pour calculer un score de risque qualifié.</p>
            </div>
            <div class="flow-step">
                <div class="fs-icon"><i class="fa-solid fa-bell"></i><span class="fs-num">3</span></div>
                <h3>Alerte</h3>
                <p>Une alerte est créée ou réutilisée si récente pour éviter les doublons inutiles.</p>
            </div>
            <div class="flow-step">
                <div class="fs-icon"><i class="fa-solid fa-folder-open"></i><span class="fs-num">4</span></div>
                <h3>Incident</h3>
                <p>Les événements suspects sont regroupés dans un incident avec timeline exploitable.</p>
            </div>
            <div class="flow-step">
                <div class="fs-icon"><i class="fa-solid fa-circle-check"></i><span class="fs-num">5</span></div>
                <h3>Réponse</h3>
                <p>Actions de protection proposées — chaque opération sensible attend votre approbation.</p>
            </div>
        </div>
    </div>
</section>

{{-- ─── CTA ─────────────────────────────────── --}}
<section class="cta-section">
    <div class="w">
        <div class="cta-box">
            <div class="cta-shield"><i class="fa-solid fa-shield-halved"></i></div>
            <h2>Surveillez les<br><em>menaces réelles</em></h2>
            <p>
                Enrôlez vos agents, configurez les seuils, suivez alertes et incidents en temps réel.
                La console SOC est prête à entrer en action.
            </p>
            <div class="cta-btns">
                <a href="{{ route('platform.dashboard') }}" class="btn btn-primary">
                    <i class="fa-solid fa-display"></i> Entrer dans la console
                </a>
                <a href="{{ route('platform.agents.index') }}" class="btn btn-ghost">
                    <i class="fa-solid fa-robot"></i> Voir les agents
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ─── FOOTER ──────────────────────────────── --}}
<footer class="site-footer">
    <div class="footer-inner">
        <div class="f-brand"><span>Ransom</span>Shield · Plateforme SOC</div>
        <nav class="f-nav">
            <a href="{{ route('platform.dashboard') }}">Console</a>
            <a href="{{ route('platform.alerts.index') }}">Alertes</a>
            <a href="{{ route('platform.incidents.index') }}">Incidents</a>
            <a href="{{ route('platform.agents.index') }}">Agents</a>
            <a href="{{ route('platform.system-settings.index') }}">Paramètres</a>
        </nav>
        <div class="f-copy">© {{ date('Y') }} — Tous droits réservés</div>
    </div>
</footer>
@endsection

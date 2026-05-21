@extends('layouts.landing')

@section('title', 'RansomShield — Plateforme anti-ransomware')

@section('content')
    <style>
        .animated-bg {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .animated-bg span {
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: color-mix(in srgb, var(--accent) 18%, transparent);
            filter: blur(18px);
            opacity: 0.35;
            animation: floatOrb 16s ease-in-out infinite alternate;
        }

        .animated-bg span:nth-child(1) {
            left: -80px;
            top: 14%;
        }

        .animated-bg span:nth-child(2) {
            right: -100px;
            top: 28%;
            background: color-mix(in srgb, var(--accent-2) 18%, transparent);
            animation-delay: 2s;
        }

        .animated-bg span:nth-child(3) {
            left: 42%;
            bottom: -130px;
            width: 340px;
            height: 340px;
            animation-delay: 4s;
        }

        @keyframes floatOrb {
            from {
                transform: translate3d(0, 0, 0) scale(1);
            }
            to {
                transform: translate3d(28px, -34px, 0) scale(1.08);
            }
        }

        .landing-wrap,
        .landing-header {
            position: relative;
            z-index: 2;
        }

        .hero {
            position: relative;
        }

        .hero-copy {
            animation: fadeUp 0.85s ease both;
        }

        .visual-panel {
            animation: fadeUp 1s ease 0.12s both, softFloat 7s ease-in-out infinite;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(22px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes softFloat {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .hero h1 {
            max-width: 900px;
        }

        .hero h1 .gradient-text {
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .live-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 22px;
        }

        .live-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 13px;
            border-radius: 999px;
            border: 1px solid var(--border-soft);
            background: var(--bg-card);
            color: var(--text-muted);
            font-weight: 800;
            font-size: 12px;
            backdrop-filter: blur(20px);
        }

        .chip-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent-2);
            box-shadow: 0 0 0 6px color-mix(in srgb, var(--accent-2) 14%, transparent);
            animation: pulseDot 1.7s ease-in-out infinite;
        }

        @keyframes pulseDot {
            0%, 100% {
                transform: scale(0.9);
                opacity: 0.85;
            }
            50% {
                transform: scale(1.12);
                opacity: 1;
            }
        }

        .soc-screen {
            transform-style: preserve-3d;
        }

        .screen-body {
            position: relative;
        }

        .scan-line {
            position: absolute;
            left: 18px;
            right: 18px;
            top: 66px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: 0.8;
            animation: scanMove 3.2s linear infinite;
            z-index: 3;
        }

        @keyframes scanMove {
            0% {
                transform: translateY(0);
                opacity: 0;
            }
            15% {
                opacity: 0.8;
            }
            85% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(260px);
                opacity: 0;
            }
        }

        .mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .mini-card {
            padding: 14px;
            border: 1px solid var(--border-soft);
            border-radius: 18px;
            background: var(--bg-card);
        }

        .mini-value {
            font-size: 24px;
            font-weight: 950;
            letter-spacing: -0.05em;
            color: var(--text-main);
        }

        .mini-label {
            color: var(--text-muted);
            font-size: 11px;
            margin-top: 4px;
            font-weight: 800;
        }

        .flow-timeline {
            position: relative;
            padding: 36px 0 72px;
        }

        .section-heading {
            text-align: center;
            max-width: 820px;
            margin: 0 auto 26px;
        }

        .section-kicker {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 24%, transparent);
            color: var(--text-main);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 14px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: clamp(30px, 5vw, 54px);
            line-height: 0.98;
            letter-spacing: -0.065em;
        }

        .section-heading p {
            color: var(--text-muted);
            line-height: 1.75;
            margin: 16px auto 0;
            max-width: 720px;
        }

        .timeline-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-top: 30px;
        }

        .timeline-step {
            position: relative;
            padding: 20px;
            min-height: 190px;
            border-radius: 26px;
            border: 1px solid var(--border-soft);
            background: var(--bg-panel);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            backdrop-filter: blur(20px);
            animation: fadeUp 0.8s ease both;
        }

        .timeline-step:nth-child(2) { animation-delay: .08s; }
        .timeline-step:nth-child(3) { animation-delay: .16s; }
        .timeline-step:nth-child(4) { animation-delay: .24s; }
        .timeline-step:nth-child(5) { animation-delay: .32s; }

        .timeline-step::after {
            content: "";
            position: absolute;
            right: -45px;
            bottom: -45px;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: color-mix(in srgb, var(--accent) 12%, transparent);
        }

        .step-number {
            position: relative;
            z-index: 1;
            width: 42px;
            height: 42px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: var(--accent);
            color: var(--accent-contrast);
            font-weight: 950;
            margin-bottom: 18px;
        }

        .timeline-step h3 {
            position: relative;
            z-index: 1;
            margin: 0;
            font-size: 17px;
            letter-spacing: -0.03em;
        }

        .timeline-step p {
            position: relative;
            z-index: 1;
            margin: 10px 0 0;
            color: var(--text-muted);
            line-height: 1.65;
            font-size: 13px;
        }

        .capability-section {
            padding-bottom: 80px;
        }

        .capability-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 28px;
        }

        .wide-card {
            border: 1px solid var(--border-soft);
            border-radius: 30px;
            background: var(--bg-panel);
            box-shadow: var(--shadow-soft);
            padding: 26px;
            backdrop-filter: blur(22px);
            min-height: 280px;
            position: relative;
            overflow: hidden;
        }

        .wide-card::before {
            content: "";
            position: absolute;
            width: 170px;
            height: 170px;
            border-radius: 50%;
            background: color-mix(in srgb, var(--accent) 12%, transparent);
            right: -70px;
            top: -70px;
        }

        .wide-card h3 {
            margin: 0;
            font-size: 24px;
            letter-spacing: -0.05em;
        }

        .wide-card p {
            color: var(--text-muted);
            line-height: 1.75;
            margin-top: 12px;
        }

        .check-list {
            display: grid;
            gap: 11px;
            margin-top: 18px;
        }

        .check-item {
            display: flex;
            gap: 10px;
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .check-item span {
            flex: 0 0 auto;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: color-mix(in srgb, var(--accent-2) 16%, transparent);
            color: var(--accent-2);
            font-weight: 950;
        }

        .cta-panel {
            margin: 0 auto 82px;
            padding: clamp(24px, 5vw, 42px);
            border-radius: 34px;
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--accent) 18%, transparent), color-mix(in srgb, var(--accent-2) 10%, transparent)),
                var(--bg-panel);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
            box-shadow: var(--shadow-soft);
            text-align: center;
        }

        .cta-panel h2 {
            margin: 0;
            font-size: clamp(30px, 5vw, 56px);
            line-height: 0.98;
            letter-spacing: -0.07em;
        }

        .cta-panel p {
            color: var(--text-muted);
            line-height: 1.75;
            max-width: 720px;
            margin: 16px auto 0;
        }

        @media (max-width: 1100px) {
            .timeline-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .capability-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 950px) {
            .visual-panel {
                animation: fadeUp 1s ease both;
            }
        }

        @media (max-width: 620px) {
            .timeline-grid,
            .mini-grid {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 45px;
            }

            .trust-row {
                margin-top: 24px;
            }

            .live-chip {
                width: 100%;
            }
        }
    </style>

    <div class="animated-bg">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <section class="landing-wrap hero">
        <div class="hero-copy">
            <div class="hero-badge">
                <span class="hero-dot"></span>
                Plateforme SOC anti-ransomware
            </div>

            <h1>
                Une console intelligente pour <span class="gradient-text">détecter</span>,
                qualifier et répondre aux comportements ransomware.
            </h1>

            <p class="hero-text">
                RansomShield transforme les événements fichiers envoyés par un agent Python en signaux de risque,
                alertes, incidents, notifications et actions de protection contrôlées. La plateforme est conçue
                pour être démontrable, structurée et crédible dans un mémoire universitaire.
            </p>

            <div class="hero-actions">
                <a href="{{ route('platform.dashboard') }}" class="btn btn-primary">
                    Ouvrir la console SOC
                </a>
                <a href="{{ route('platform.system-settings.index') }}" class="btn btn-soft">
                    Personnaliser la plateforme
                </a>
            </div>

            <div class="live-chip-row">
                <div class="live-chip"><span class="chip-dot"></span> Analyse comportementale</div>
                <div class="live-chip"><span class="chip-dot"></span> Réponse avec validation humaine</div>
                <div class="live-chip"><span class="chip-dot"></span> Timeline incident prête</div>
            </div>

            <div class="trust-row">
                <div class="trust-card">
                    <div class="trust-number">{{ $agentsCount }}</div>
                    <div class="trust-label">machine surveillée ou agent enrôlé actuellement</div>
                </div>

                <div class="trust-card">
                    <div class="trust-number">{{ $openAlertsCount }}</div>
                    <div class="trust-label">alerte ouverte ou en investigation</div>
                </div>

                <div class="trust-card">
                    <div class="trust-number">{{ $openIncidentsCount }}</div>
                    <div class="trust-label">incident actif suivi par la console</div>
                </div>
            </div>
        </div>

        <div class="visual-panel">
            <div class="soc-screen">
                <div class="screen-top">
                    <span class="screen-dot"></span>
                    <span class="screen-dot"></span>
                    <span class="screen-dot"></span>
                </div>

                <div class="screen-body">
                    <div class="scan-line"></div>

                    <div class="risk-card">
                        <div class="risk-title">
                            <span>Détection ransomware</span>
                            <span class="risk-pill">critical</span>
                        </div>
                        <div class="bar">
                            <div class="bar-fill"></div>
                        </div>
                    </div>

                    <div class="mini-grid">
                        <div class="mini-card">
                            <div class="mini-value">45</div>
                            <div class="mini-label">score extension .locked</div>
                        </div>
                        <div class="mini-card">
                            <div class="mini-value">5</div>
                            <div class="mini-label">actions proposées</div>
                        </div>
                    </div>

                    <div class="flow-list">
                        <div class="flow-item">
                            <strong>Agent Python</strong>
                            <span>événement fichier</span>
                        </div>

                        <div class="flow-item">
                            <strong>Moteur de risque</strong>
                            <span>score + niveau</span>
                        </div>

                        <div class="flow-item">
                            <strong>Incident</strong>
                            <span>alerte + timeline</span>
                        </div>

                        <div class="flow-item">
                            <strong>Admin</strong>
                            <span>approuve ou rejette</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-wrap flow-timeline">
        <div class="section-heading">
            <div class="section-kicker">Flux de sécurité</div>
            <h2>Du fichier suspect à la décision administrateur.</h2>
            <p>
                Le système suit une chaîne claire : observer, analyser, qualifier, alerter,
                créer l’incident et proposer une réponse sans action dangereuse automatique.
            </p>
        </div>

        <div class="timeline-grid">
            <article class="timeline-step">
                <div class="step-number">1</div>
                <h3>Collecte agent</h3>
                <p>L’agent Python observe les créations, modifications, suppressions, renommages et extensions suspectes.</p>
            </article>

            <article class="timeline-step">
                <div class="step-number">2</div>
                <h3>Analyse risque</h3>
                <p>Laravel applique les règles, seuils et extensions sensibles pour calculer un score et un niveau.</p>
            </article>

            <article class="timeline-step">
                <div class="step-number">3</div>
                <h3>Alerte</h3>
                <p>Une alerte est créée ou réutilisée si elle est récente, afin d’éviter les doublons inutiles.</p>
            </article>

            <article class="timeline-step">
                <div class="step-number">4</div>
                <h3>Incident</h3>
                <p>Le système regroupe les événements suspects dans un incident exploitable par l’administrateur.</p>
            </article>

            <article class="timeline-step">
                <div class="step-number">5</div>
                <h3>Réponse</h3>
                <p>Les actions de protection sont proposées avec validation humaine pour les opérations sensibles.</p>
            </article>
        </div>
    </section>

    <section class="landing-wrap capability-section">
        <div class="section-heading">
            <div class="section-kicker">Capacités clés</div>
            <h2>Une plateforme pensée pour démontrer un vrai raisonnement SOC.</h2>
        </div>

        <div class="capability-grid">
            <article class="wide-card">
                <h3>Détection orientée comportement</h3>
                <p>
                    RansomShield ne se limite pas à chercher un nom de virus. Le système observe les comportements :
                    changements massifs, extensions suspectes, notes de rançon, activité sur dossiers partagés
                    et signaux multi-hôtes.
                </p>

                <div class="check-list">
                    <div class="check-item"><span>✓</span> Règles modifiables depuis Laravel.</div>
                    <div class="check-item"><span>✓</span> Seuils d’analyse configurables.</div>
                    <div class="check-item"><span>✓</span> Extensions importantes et suspectes séparées.</div>
                </div>
            </article>

            <article class="wide-card">
                <h3>Réponse contrôlée et défendable</h3>
                <p>
                    Les actions sensibles ne sont pas exécutées dangereusement par défaut.
                    Le système propose, l’administrateur décide, et l’historique reste exploitable
                    pour la timeline d’incident.
                </p>

                <div class="check-list">
                    <div class="check-item"><span>✓</span> Isolation hôte en mode manuel si non autorisée.</div>
                    <div class="check-item"><span>✓</span> Kill process gardé en validation humaine.</div>
                    <div class="check-item"><span>✓</span> Actions, décisions et notifications historisées.</div>
                </div>
            </article>
        </div>
    </section>

    <section class="landing-wrap cta-panel">
        <h2>Prêt à superviser les événements réels ?</h2>
        <p>
            La console SOC permet de suivre les machines, alertes, incidents, actions de protection,
            règles, seuils et paramètres. La prochaine étape sera d’habiller toutes les pages internes.
        </p>

        <div class="hero-actions" style="justify-content:center;">
            <a href="{{ route('platform.dashboard') }}" class="btn btn-primary">Entrer dans la console</a>
            <a href="{{ route('platform.agents.index') }}" class="btn btn-soft">Voir les agents</a>
        </div>
    </section>
@endsection

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f1e4">
    <meta name="description" content="Warga Digital — Platform web tata kelola administrasi, transparansi kas, dan ruang komunitas RT/RW yang modern, mudah, dan akuntabel.">
    <title>Warga Digital — Satu Ketuk, Semua Urusan Warga Beres</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            color-scheme: light;
            --cream: #f6f1e4;
            --cream-soft: #faf7f0;
            --paper: #ffffff;
            --ink: #111827;
            --ink-secondary: #374151;
            --muted: #4b5563;
            --green: #10231e;
            --green-soft: #1b342d;
            --orange: #d97706;
            --orange-hover: #b45309;
            --line: #e4ded1;
            --line-light: #ece7dc;
            --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
            --ease-drawer: cubic-bezier(0.32, 0.72, 0, 1);
            --font: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        * { box-sizing: border-box; }
        html {
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: rgba(29, 33, 30, 0.28) transparent;
        }
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: rgba(29, 33, 30, 0.24);
            border-radius: 999px;
            transition: background .2s ease;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(29, 33, 30, 0.48); }

        body {
            margin: 0;
            background: var(--cream);
            color: var(--ink);
            font-family: var(--font);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }
        .landing { overflow: hidden; position: relative; }
        .container { width: min(1200px, calc(100% - 48px)); margin-inline: auto; position: relative; z-index: 1; }
        
        .eyebrow {
            margin: 0 0 12px;
            color: #555e50;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .eyebrow-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--orange);
            display: inline-block;
            flex-shrink: 0;
        }
        .eyebrow-light {
            color: #fde68a;
        }
        .eyebrow-light .eyebrow-dot {
            background: #fde68a;
        }
        .section-title {
            margin: 0;
            font-size: clamp(30px, 3.4vw, 44px);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -.035em;
            color: var(--ink);
        }
        .accent { color: var(--orange); font-weight: 800; }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 22px;
            border-radius: 999px;
            font-size: 13.5px;
            font-weight: 700;
            transition: transform 180ms var(--ease-out), box-shadow 180ms var(--ease-out), background-color 150ms ease, border-color 150ms ease;
            cursor: pointer;
            text-decoration: none;
            gap: 8px;
            user-select: none;
            -webkit-user-select: none;
        }
        .button:hover { transform: translateY(-2px); }
        .button:active { transform: scale(0.96); }
        .button-primary {
            background: #e5a53f;
            color: #1f1b13;
            box-shadow: 0 4px 14px rgba(180, 120, 30, 0.22);
            border: 1px solid rgba(180, 120, 30, 0.3);
        }
        .button-primary:hover {
            background: #edaf4a;
            box-shadow: 0 6px 18px rgba(180, 120, 30, 0.32);
        }
        .button-quiet {
            border: 1px solid #c9c5bc;
            background: #dedad0;
            color: #29251d;
        }
        .button-quiet:hover {
            background: #e7e3d9;
            border-color: #beb9ae;
        }
        .button-dark {
            background: var(--green);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .button-dark:hover {
            background: var(--green-soft);
            box-shadow: 0 6px 20px rgba(16, 35, 30, 0.3);
        }
        .button-ghost {
            background: transparent;
            border: 1px solid transparent;
            color: var(--ink-secondary);
            padding-inline: 14px;
        }
        .button-ghost:hover {
            color: var(--ink);
            background: rgba(0, 0, 0, 0.04);
            border-color: rgba(0, 0, 0, 0.06);
        }

        /* Offset Anchor Scroll */
        #beranda, #tentang, #fitur, #cara-kerja, #peran, #faq {
            scroll-margin-top: 86px;
        }

        /* Sticky Header - Kontras Tinggi & Bersih */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid var(--line);
            background: rgba(246, 241, 228, 0.94);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: background .2s ease, box-shadow .2s ease;
        }
        .site-header.is-scrolled {
            background: rgba(246, 241, 228, 0.98);
            box-shadow: 0 4px 18px -2px rgba(29, 33, 30, 0.06);
        }
        .nav-wrap {
            display: flex;
            min-height: 76px;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            flex-shrink: 0;
        }
        .brand-mark {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(16, 35, 30, 0.2);
            flex-shrink: 0;
        }
        .brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            display: block;
        }
        .brand-name {
            font-size: 17px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.03em;
            color: var(--ink);
        }
        .brand-name span {
            display: block;
            margin-top: 3px;
            color: #555e50;
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        /* Teks Navigasi - Jelas, Kontras Tinggi, Terbaca */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            color: #1f2937;
            font-size: 14.5px;
            font-weight: 600;
        }
        .nav-links a {
            transition: color .2s ease;
            position: relative;
            padding: 4px 0;
        }
        .nav-links a:hover {
            color: var(--orange);
        }
        .nav-links a.active {
            color: #10231e;
            font-weight: 800;
        }
        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--orange);
            border-radius: 2px;
        }
        .nav-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 700;
            background: #dcfce7;
            color: #166534;
            margin-left: 4px;
            vertical-align: middle;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nav-login-link {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
            padding: 6px 12px;
            border-radius: 8px;
            transition: color .2s ease, background .2s ease;
        }
        .nav-login-link:hover {
            color: var(--orange);
            background: rgba(0, 0, 0, 0.04);
        }
        .menu-toggle {
            display: none;
            border: 0;
            background: transparent;
            color: var(--ink);
            cursor: pointer;
            padding: 6px;
        }

        /* Hero */
        .hero {
            position: relative;
            min-height: 540px;
            display: grid;
            align-items: center;
            overflow: hidden;
            padding-block: 30px 48px;
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1.25fr;
            align-items: center;
            gap: 32px;
        }
        .hero-copy {
            position: relative;
            z-index: 2;
            max-width: 500px;
        }
        .hero h1 {
            margin: 0;
            font-size: clamp(38px, 4.6vw, 62px);
            line-height: 1.05;
            letter-spacing: -.035em;
            font-weight: 800;
            color: var(--ink);
        }
        .hero-copy > p:not(.eyebrow) {
            max-width: 460px;
            margin: 18px 0 28px;
            color: var(--ink-secondary);
            font-size: 15.5px;
            font-weight: 500;
            line-height: 1.6;
        }
        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }
        .corner-gradient {
            position: absolute;
            top: 0;
            right: 0;
            width: clamp(280px, 34vw, 500px);
            height: clamp(280px, 34vw, 500px);
            border-radius: 0 0 0 100%;
            background: radial-gradient(circle at 100% 0%, rgba(229, 165, 63, 0.15) 0%, rgba(229, 165, 63, 0.04) 50%, transparent 72%);
            pointer-events: none;
            z-index: 0;
        }
        .hero-visual {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-image {
            width: 100%;
            height: auto;
            max-width: 820px;
            display: block;
            user-select: none;
            border-radius: 16px;
        }

        /* Stats Ribbon - Dampak Nyata Operasional (Bukan Metrik Abstrak) */
        .stats {
            background: var(--green);
            color: white;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            padding-block: 40px;
        }
        .stat {
            padding-inline: 12px;
            border-left: 1px solid rgba(255, 255, 255, 0.12);
        }
        .stat:first-child { border-left: none; }
        .stat strong {
            display: block;
            margin-bottom: 4px;
            color: #f59e0b;
            font-size: clamp(26px, 2.7vw, 36px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.02em;
        }
        .stat span {
            display: block;
            color: #d1d5db;
            font-size: 13.5px;
            font-weight: 500;
            line-height: 1.45;
        }

        /* Section Masalah - Desain Bersih & Ikon Presisi */
        .intro-section { padding-block: 80px 96px; position: relative; }
        .intro-heading { max-width: 680px; margin-bottom: 32px; }
        .intro-heading > p:last-child {
            margin: 14px 0 0;
            color: var(--ink-secondary);
            font-size: 15.5px;
            font-weight: 500;
            line-height: 1.6;
        }
        .benefit-grid {
            display: grid;
            width: min(1060px, 100%);
            margin-inline: auto;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .benefit-card {
            display: flex;
            gap: 16px;
            padding: 22px 20px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 18px;
            background: var(--paper);
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .benefit-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.06);
        }
        .icon-circle {
            display: grid;
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            place-items: center;
            border-radius: 14px;
            background: #fef3c7;
            color: var(--orange);
        }
        .icon-circle svg { width: 24px; height: 24px; }
        .benefit-card h3 {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.02em;
        }
        .benefit-card p {
            margin: 0;
            color: #4b5563;
            font-size: 13.5px;
            font-weight: 500;
            line-height: 1.5;
        }

        /* Platform / Section 7 Fitur Resmi */
        .platform-section { padding-block: 64px 88px; position: relative; }
        .center-heading { max-width: 740px; margin: 0 auto 36px; text-align: center; }
        .center-heading > p:last-child {
            max-width: 660px;
            margin: 14px auto 0;
            color: var(--ink-secondary);
            font-size: 15.5px;
            font-weight: 500;
            line-height: 1.6;
        }
        .feature-grid {
            display: grid;
            width: 100%;
            grid-template-columns: repeat(12, 1fr);
            gap: 18px;
        }
        .feature-card {
            min-height: 240px;
            padding: 24px 22px;
            border: 1px solid var(--line);
            border-radius: 20px;
            background: var(--paper);
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-start;
            transition: transform 200ms var(--ease-out), box-shadow 200ms var(--ease-out), border-color 200ms var(--ease-out);
            cursor: pointer;
            position: relative;
        }
        .feature-card.col-4 {
            grid-column: span 4;
        }
        .feature-card.col-3 {
            grid-column: span 3;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.07);
            border-color: rgba(217, 119, 6, 0.35);
        }
        .feature-card:active {
            transform: scale(0.99);
        }
        .feature-icon {
            display: grid;
            width: 46px;
            height: 46px;
            place-items: center;
            margin-bottom: 14px;
            border-radius: 12px;
            background: #fef3c7;
            color: var(--orange);
        }
        .feature-icon svg { width: 24px; height: 24px; }
        .feature-icon.blue { background: #dbeafe; color: #1d4ed8; }
        .feature-icon.green { background: #dcfce7; color: #15803d; }
        .feature-icon.purple { background: #f3e8ff; color: #6b21a8; }
        .feature-icon.rose { background: #ffe4e6; color: #be123c; }
        .feature-icon.indigo { background: #e0e7ff; color: #4338ca; }

        .feature-card h3 {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.02em;
            line-height: 1.3;
        }
        .feature-card p {
            min-height: 44px;
            margin: 0 0 14px;
            color: #4b5563;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.5;
            flex-grow: 1;
        }
        .feature-card-btn {
            color: var(--orange);
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .feature-card:hover .feature-card-btn {
            text-decoration: underline;
        }

        /* Section Cara Kerja - Tanpa Strip Pelangi AI, Clean Minimalist */
        .experience-section { padding-block: 68px 84px; position: relative; }
        .experience-panel {
            position: relative;
            background: linear-gradient(180deg, #ffffff 0%, #faf8f3 100%);
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 46px 36px 50px;
            box-shadow: 0 8px 28px rgba(17, 29, 26, 0.04);
            overflow: hidden;
        }
        /* Bersihkan strip gradasi AI lama: diganti aksen minimalis borderless */
        .experience-header {
            max-width: 720px;
            margin: 0 auto 40px;
            text-align: center;
        }
        .experience-header .eyebrow { justify-content: center; }
        .experience-intro {
            max-width: 620px;
            margin: 12px auto 0;
            color: var(--ink-secondary);
            font-size: 15px;
            font-weight: 500;
            line-height: 1.55;
        }
        .experience-journey {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            position: relative;
        }
        .exp-card {
            background: #ffffff;
            border: 1px solid #ebe6dc;
            border-radius: 18px;
            padding: 20px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.02);
        }
        .exp-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .exp-step-badge {
            font-size: 13px;
            font-weight: 800;
            color: var(--green);
            background: #e6ece8;
            padding: 3px 9px;
            border-radius: 999px;
        }
        .exp-step-tag {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--orange);
        }
        .exp-card-copy h3 {
            margin: 0 0 6px;
            font-size: 15.5px;
            font-weight: 700;
            color: var(--ink);
        }
        .exp-card-copy p {
            margin: 0 0 16px;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.5;
            min-height: 54px;
        }
        .exp-mini-ui {
            background: #f8f6f0;
            border: 1px solid #e7e2d6;
            border-radius: 12px;
            padding: 12px;
            font-size: 12px;
        }
        .exp-ui-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #eae5d9;
        }
        .exp-ui-channel {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: #1f2937;
        }
        .exp-dot-pulse {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #16a34a;
        }
        .exp-dot-pulse.amber { background: #d97706; }
        .exp-ui-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            background: #dcfce7;
            color: #15803d;
        }
        .exp-ui-badge.amber { background: #fef3c7; color: #b45309; }
        .exp-field-row { margin-bottom: 6px; }
        .exp-field-lbl { display: block; font-size: 10px; color: #6b7280; font-weight: 600; margin-bottom: 2px; }
        .exp-field-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border: 1px solid #dcd7cb;
            border-radius: 6px;
            padding: 4px 8px;
            font-weight: 600;
            color: #111827;
        }
        .exp-field-verify { color: #16a34a; font-size: 10.5px; font-weight: 700; }
        .exp-action-btn {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #10231e;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 700;
            margin-top: 8px;
            font-size: 11px;
        }
        .exp-menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            border: 1px solid #e0dbce;
            border-radius: 6px;
            padding: 6px 8px;
            margin-bottom: 4px;
        }
        .exp-menu-item.is-active {
            border-color: #d97706;
            background: #fffbeb;
        }
        .exp-menu-left { display: flex; align-items: center; gap: 8px; }
        .exp-menu-icon {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            background: #fef3c7;
            color: var(--orange);
        }
        .exp-menu-icon.green {
            background: #dcfce7;
            color: #15803d;
        }
        .exp-menu-icon svg {
            width: 13px;
            height: 13px;
        }
        .exp-menu-name { display: block; font-size: 11px; font-weight: 700; color: #111827; }
        .exp-menu-sub { display: block; font-size: 9.5px; color: #6b7280; }
        .exp-check-pill { font-size: 9.5px; font-weight: 700; color: #b45309; background: #fde68a; padding: 1px 5px; border-radius: 4px; }

        .exp-stepper { display: flex; flex-direction: column; gap: 6px; }
        .exp-step-node { display: flex; align-items: flex-start; gap: 8px; }
        .exp-node-bullet {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 9px;
            font-weight: 800;
            background: #e5e7eb;
            color: #6b7280;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .exp-step-node.is-done .exp-node-bullet { background: #dcfce7; color: #15803d; }
        .exp-step-node.is-active .exp-node-bullet { background: #fef3c7; color: #b45309; }
        .exp-node-text strong { display: block; font-size: 11px; font-weight: 700; color: #111827; }
        .exp-node-text small { display: block; font-size: 9.5px; color: #6b7280; }

        .exp-doc-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            border: 1px solid #dcd7cb;
            border-radius: 6px;
            padding: 6px 8px;
            margin-bottom: 6px;
        }
        .exp-doc-icon-wrap {
            background: #fee2e2;
            color: #dc2626;
            font-size: 9px;
            font-weight: 800;
            padding: 4px 6px;
            border-radius: 4px;
        }
        .exp-doc-title { display: block; font-size: 11px; font-weight: 700; color: #111827; }
        .exp-doc-meta { display: block; font-size: 9.5px; color: #6b7280; }
        .exp-doc-status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10.5px;
            color: #166534;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .exp-download-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #e5a53f;
            color: #1f1b13;
            padding: 6px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 11px;
        }

        /* Spill Fitur Khusus: Portal Verifikasi Surat Publik */
        .verify-banner-section {
            padding-block: 40px 60px;
        }
        .verify-banner {
            background: linear-gradient(135deg, #10231e 0%, #17342d 100%);
            color: white;
            border-radius: 24px;
            padding: 40px 44px;
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            align-items: center;
            gap: 36px;
            box-shadow: 0 12px 36px rgba(16, 35, 30, 0.2);
            position: relative;
            overflow: hidden;
        }
        .verify-banner::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(229, 165, 63, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .verify-banner-copy h2 {
            margin: 0 0 12px;
            font-size: clamp(24px, 2.5vw, 32px);
            font-weight: 800;
            line-height: 1.25;
            color: #ffffff;
        }
        .verify-banner-copy p {
            margin: 0 0 24px;
            color: #d1d5db;
            font-size: 14.5px;
            line-height: 1.6;
            max-width: 520px;
        }
        .verify-card-mockup {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
            border-radius: 18px;
            padding: 22px;
            color: white;
        }
        .verify-mockup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }
        .verify-mockup-code {
            font-family: monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            color: #fde68a;
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .verify-mockup-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #86efac;
            font-size: 12px;
            font-weight: 700;
        }
        .verify-mockup-type {
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.04em;
        }
        .verify-mockup-status {
            color: #86efac;
        }
        .verify-mockup-meta {
            font-size: 12px;
            color: #d1d5db;
            line-height: 1.5;
        }

        /* Dirancang untuk Bertumbuh (Growth Section) */
        .growth-section { padding-block: 60px 80px; }
        .growth-content { text-align: center; }
        .growth-content .eyebrow { justify-content: center; }
        .growth-intro {
            max-width: 660px;
            margin: 14px auto 36px;
            color: var(--ink-secondary);
            font-size: 15px;
            line-height: 1.6;
        }
        .growth-steps {
            display: grid;
            grid-template-columns: 1fr auto 1fr auto 1fr;
            align-items: center;
            gap: 16px;
            max-width: 1040px;
            margin: 0 auto;
        }
        .growth-card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 24px 20px;
            text-align: left;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
        }
        .growth-card small {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--orange);
            margin-bottom: 6px;
        }
        .growth-card h3 {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
        }
        .growth-card p {
            margin: 0;
            font-size: 13px;
            color: #4b5563;
            line-height: 1.5;
        }
        .growth-arrow { font-size: 24px; color: #9ca3af; }

        /* Peran Pengguna - Menggunakan Foto Lokal Offline */
        .roles-section { padding-block: 70px 90px; }
        .roles-heading { max-width: 600px; margin-bottom: 32px; }
        .role-gallery {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
        }
        .role-card {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            height: 380px;
            background: #111827;
            cursor: pointer;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .role-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        }
        .role-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: filter .3s ease;
        }
        .role-front {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 40%, rgba(16, 35, 30, 0.95) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 20px 16px;
            color: white;
            transition: opacity .25s ease;
        }
        .role-name { font-size: 17px; font-weight: 800; margin-bottom: 4px; }
        .role-hint { font-size: 12px; color: #fde68a; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        
        .role-detail {
            position: absolute;
            inset: 0;
            background: rgba(16, 35, 30, 0.97);
            color: white;
            padding: 20px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            opacity: 0;
            transform: translateY(12px);
            pointer-events: none;
            transition: opacity 220ms var(--ease-out), transform 220ms var(--ease-out);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .role-card.is-active .role-detail {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .role-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .role-badge {
            font-size: 10.5px;
            font-weight: 700;
            color: #f59e0b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .role-detail-title { margin: 2px 0 0; font-size: 16px; font-weight: 800; color: white; }
        .role-close-btn {
            background: rgba(255, 255, 255, 0.12);
            border: 0;
            color: white;
            font-size: 18px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            cursor: pointer;
            transition: transform 150ms var(--ease-out);
        }
        .role-close-btn:active {
            transform: scale(0.92);
        }
        .role-desc { margin: 0 0 10px; font-size: 12px; color: #d1d5db; line-height: 1.45; }
        .role-duties-label { font-size: 11px; font-weight: 700; color: #fde68a; margin-bottom: 4px; display: block; }
        .role-duties-list {
            margin: 0;
            padding-left: 14px;
            font-size: 11px;
            color: #e5e7eb;
            line-height: 1.45;
        }
        .role-duties-list li { margin-bottom: 4px; }

        /* FAQ Accordion */
        .faq-section { padding-block: 70px 90px; }
        .faq-accordion {
            max-width: 820px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .faq-item {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            transition: border-color .2s ease;
        }
        .faq-item.is-open { border-color: var(--orange); }
        .faq-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            font-size: 15.5px;
            font-weight: 700;
            color: var(--ink);
            gap: 16px;
        }
        .faq-number { color: var(--orange); font-size: 14px; font-weight: 800; }
        .faq-question { flex: 1; }
        .faq-icon {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
            transition: transform 240ms var(--ease-out);
        }
        .faq-item.is-open .faq-icon { transform: rotate(180deg); }
        .faq-answer-wrapper {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 250ms var(--ease-out);
        }
        .faq-item.is-open .faq-answer-wrapper {
            grid-template-rows: 1fr;
        }
        .faq-answer-content {
            overflow: hidden;
            padding: 0 22px;
            color: var(--ink-secondary);
            font-size: 14.5px;
            line-height: 1.6;
            transition: padding-bottom 250ms var(--ease-out);
        }
        .faq-item.is-open .faq-answer-content {
            padding-bottom: 20px;
        }
        .faq-answer-content p { margin: 0; }

        /* Final CTA */
        .cta-section { padding-block: 20px 80px; }
        .cta-panel {
            background: linear-gradient(135deg, var(--green) 0%, #1a3830 100%);
            border-radius: 28px;
            padding: 60px 40px;
            text-align: center;
            color: white;
            box-shadow: 0 12px 36px rgba(16, 35, 30, 0.2);
        }
        .cta-copy h2 {
            margin: 0 0 14px;
            font-size: clamp(28px, 3.2vw, 42px);
            font-weight: 800;
            line-height: 1.2;
            color: white;
        }
        .cta-copy p {
            max-width: 580px;
            margin: 0 auto 30px;
            color: #d1d5db;
            font-size: 15.5px;
            line-height: 1.6;
        }
        .cta-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        /* Footer - Bersih, Elegan, Tanpa Fake Map */
        .site-footer {
            background: #0d1a16;
            color: #d1d5db;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-block: 60px 32px;
            font-size: 14px;
        }
        .footer-main {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 48px;
        }
        .footer-brand p {
            margin: 14px 0 0;
            color: #9ca3af;
            font-size: 13.5px;
            line-height: 1.6;
            max-width: 340px;
        }
        .footer-brand .brand-name { color: #ffffff; }
        .footer-brand .brand-name span { color: #a7f3d0; }
        .footer-links h3 {
            margin: 0 0 16px;
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .footer-links a {
            display: block;
            margin-bottom: 10px;
            color: #9ca3af;
            transition: color .2s ease;
        }
        .footer-links a:hover { color: #f59e0b; }
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 13px;
            color: #6b7280;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Feature Side Drawer (Off-Canvas) */
        .drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(4px);
            z-index: 90;
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s ease;
        }
        .drawer-overlay.is-active {
            opacity: 1;
            pointer-events: auto;
        }
        .feature-drawer {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: min(480px, 100vw);
            background: #ffffff;
            z-index: 100;
            box-shadow: -10px 0 40px rgba(0, 0, 0, 0.18);
            transform: translateX(100%);
            transition: transform 320ms var(--ease-drawer);
            display: flex;
            flex-direction: column;
        }
        .feature-drawer.is-active {
            transform: translateX(0);
        }
        .drawer-header {
            padding: 24px 28px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafafa;
        }
        .drawer-header-meta {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .drawer-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: #fef3c7;
            color: var(--orange);
        }
        .drawer-icon-wrap.blue { background: #dbeafe; color: #1d4ed8; }
        .drawer-icon-wrap.green { background: #dcfce7; color: #15803d; }
        .drawer-icon-wrap.purple { background: #f3e8ff; color: #6b21a8; }
        .drawer-icon-wrap.rose { background: #ffe4e6; color: #be123c; }
        .drawer-icon-wrap.indigo { background: #e0e7ff; color: #4338ca; }
        .drawer-icon-wrap svg { width: 24px; height: 24px; }
        .drawer-badge {
            font-size: 11px;
            font-weight: 700;
            color: var(--orange);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .drawer-badge.blue { color: #1d4ed8; }
        .drawer-badge.green { color: #15803d; }
        .drawer-badge.purple { color: #6b21a8; }
        .drawer-badge.rose { color: #be123c; }
        .drawer-badge.indigo { color: #4338ca; }
        .drawer-title { margin: 2px 0 0; font-size: 18px; font-weight: 800; color: #111827; }
        .drawer-close-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            color: #6b7280;
            display: grid;
            place-items: center;
            transition: background .2s ease;
        }
        .drawer-close-btn:hover { background: #e5e7eb; color: #111827; }
        .drawer-body {
            padding: 28px;
            overflow-y: auto;
            flex: 1;
        }
        .drawer-desc-box {
            background: #f9fafb;
            border-left: 3px solid var(--orange);
            padding: 14px 16px;
            border-radius: 0 10px 10px 0;
            margin-bottom: 24px;
        }
        .drawer-desc { margin: 0; color: #374151; font-size: 14px; line-height: 1.6; }
        .drawer-section { margin-bottom: 24px; }
        .drawer-section-title {
            margin: 0 0 14px;
            font-size: 14px;
            font-weight: 800;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-indicator { width: 4px; height: 14px; background: var(--orange); border-radius: 2px; }
        .drawer-steps-list { display: flex; flex-direction: column; gap: 12px; }
        .drawer-step-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .drawer-step-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #fef3c7;
            color: var(--orange);
            font-size: 11px;
            font-weight: 800;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .drawer-step-badge.blue { background: #dbeafe; color: #1d4ed8; }
        .drawer-step-badge.green { background: #dcfce7; color: #15803d; }
        .drawer-step-badge.purple { background: #f3e8ff; color: #6b21a8; }
        .drawer-step-badge.rose { background: #ffe4e6; color: #be123c; }
        .drawer-step-badge.indigo { background: #e0e7ff; color: #4338ca; }
        .drawer-step-name { display: block; font-size: 13.5px; font-weight: 700; color: #111827; }
        .drawer-step-desc { display: block; font-size: 12.5px; color: #6b7280; line-height: 1.45; }
        .drawer-benefits-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
        .drawer-benefit-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            color: #374151;
        }
        .drawer-benefit-check {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #dcfce7;
            color: #15803d;
            font-size: 11px;
            font-weight: 800;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .drawer-footer {
            padding: 20px 28px;
            border-top: 1px solid #e5e7eb;
            background: #fafafa;
        }
        .drawer-footer-note {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            color: #6b7280;
            margin-bottom: 12px;
        }
        .pulse-dot { width: 6px; height: 6px; border-radius: 50%; background: #16a34a; }
        .drawer-footer-actions {
            display: flex;
            gap: 10px;
        }
        .drawer-btn-dismiss {
            flex: 1;
        }
        .drawer-btn-cta {
            flex: 1.6;
        }

        /* Media Queries Responsif */
        @media (max-width: 1024px) {
            .hero-grid { grid-template-columns: 1fr; text-align: center; }
            .hero-copy { max-width: 100%; margin: 0 auto; }
            .hero h1 { margin-inline: auto; }
            .hero-copy > p:not(.eyebrow) { margin-inline: auto; }
            .hero-buttons { justify-content: center; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .stat:nth-child(3) { border-left: none; }
            .feature-grid { grid-template-columns: repeat(2, 1fr); }
            .feature-card.col-4,
            .feature-card.col-3 { grid-column: span 1; }
            .feature-card:last-child { grid-column: span 2; }
            .experience-journey { grid-template-columns: repeat(2, 1fr); }
            .role-gallery { grid-template-columns: repeat(3, 1fr); }
            .growth-steps { grid-template-columns: 1fr; gap: 12px; }
            .growth-arrow { display: none; }
            .verify-banner { grid-template-columns: 1fr; }
            .footer-main { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .container { width: calc(100% - 32px); }
            .menu-toggle { display: block; }
            .nav-links {
                display: none;
                position: absolute;
                top: 76px;
                left: 0;
                right: 0;
                background: #f6f1e4;
                border-bottom: 1px solid var(--line);
                flex-direction: column;
                padding: 20px 24px;
                gap: 16px;
                align-items: flex-start;
                box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
            }
            .nav-links.is-open { display: flex; }
            .stats-grid { grid-template-columns: 1fr; gap: 20px; }
            .stat { border-left: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 16px; }
            .benefit-grid { grid-template-columns: 1fr; }
            .feature-grid { grid-template-columns: 1fr; }
            .feature-card.col-4,
            .feature-card.col-3,
            .feature-card:last-child { grid-column: span 1; }
            .experience-journey { grid-template-columns: 1fr; }
            .role-gallery { grid-template-columns: 1fr; }
            .role-card { height: 320px; }
            .footer-main { grid-template-columns: 1fr; gap: 28px; }
            .footer-bottom { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
<div class="landing">

    <!-- STICKY HEADER - KONTRAST TINGGI & RAMAH WARGA -->
    <header class="site-header">
        <div class="container nav-wrap">
            <a class="brand" href="#beranda" aria-label="Warga Digital, Beranda">
                <span class="brand-mark" aria-hidden="true">
                    <img src="{{ asset('assets/logo.png') }}" alt="Logo Warga Digital" width="40" height="40">
                </span>
                <span class="brand-name">Warga<span>Digital</span></span>
            </a>

            <!-- Navigasi Anchor & Tautan Fungsional -->
            <nav class="nav-links" id="site-navigation" aria-label="Navigasi Utama">
                <a href="#tentang">Solusi</a>
                <a href="#fitur">Fitur Resmi</a>
                <a href="#cara-kerja">Cara Kerja</a>
                <a href="#peran">Peran Warga</a>
                <a href="{{ route('verifikasi.index') }}">
                    Verifikasi Surat
                    <span class="nav-badge">Publik</span>
                </a>
            </nav>

            <div class="nav-actions">
                <a class="nav-login-link" href="{{ route('first-time.form') }}">Aktivasi Akun</a>
                <a class="button button-primary" href="{{ route('login') }}">
                    <span>Masuk Portal</span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <button class="menu-toggle" type="button" aria-label="Buka Navigasi" aria-expanded="false" aria-controls="site-navigation" onclick="const nav = document.getElementById('site-navigation'); const opened = nav.classList.toggle('is-open'); this.setAttribute('aria-expanded', opened);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main>
        <!-- HERO SECTION -->
        <section class="hero" id="beranda">
            <div class="corner-gradient" aria-hidden="true"></div>
            <div class="container hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span>Platform Pelayanan Warga RT/RW</p>
                    <h1>Satu Ketuk,<br>Semua Urusan<br><span class="accent">Warga </span>Beres.</h1>
                    <p>Dari pengajuan surat berstempel digital, transparansi arus kas, hingga ruang komunitas terpadu. Membantu tata kelola lingkungan yang mudah digunakan siapa saja, dari remaja hingga lansia.</p>
                    <div class="hero-buttons">
                        <a class="button button-primary" href="{{ route('login') }}">
                            <span>Masuk Portal Warga</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <a class="button button-quiet" href="{{ route('first-time.form') }}">Aktivasi Akun</a>
                        <a class="button button-ghost" href="#fitur">Jelajahi Fitur &darr;</a>
                    </div>
                </div>
                <div class="hero-visual" aria-label="Ilustrasi Dasbor Warga Digital">
                    <img src="{{ asset('assets/test-hero.png') }}" alt="Ilustrasi Dasbor Warga Digital pada laptop dan ponsel" class="hero-image" width="1200" height="800" loading="eager">
                </div>
            </div>
        </section>

        <!-- STATS RIBBON - DAMPAK NYATA OPERASIONAL -->
        <section class="stats" aria-label="Keunggulan Utama Platform">
            <div class="container stats-grid">
                <div class="stat">
                    <strong>100% Mandiri</strong>
                    <span>Urus surat &amp; cek kas langsung dari rumah</span>
                </div>
                <div class="stat">
                    <strong>&lt; 5 Menit</strong>
                    <span>Pengajuan surat instan terhubung ke pengurus RT</span>
                </div>
                <div class="stat">
                    <strong>6 Jenis Surat</strong>
                    <span>SKD, SKU, SKTM, SPKK, SKL &amp; SKKm sah ber-QR</span>
                </div>
                <div class="stat">
                    <strong>100% Transparan</strong>
                    <span>Arus kas mutasi &amp; audit trail forensik real-time</span>
                </div>
            </div>
        </section>

        <!-- SECTION MASALAH - IKON PRESISI BERSIH -->
        <section class="intro-section container" id="tentang">
            <div class="intro-heading">
                <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span>Tantangan Pengelolaan Lingkungan</p>
                <h2 class="section-title">Lingkungan ramai warga,<br>tapi <span class="accent">sepi koordinasi.</span></h2>
                <p>Urusan administrasi, iuran, dan informasi warga masih terpecah di berbagai pintu, membuat koordinasi RT/RW kurang praktis dan rentan salah paham.</p>
            </div>
            <div class="benefit-grid">
                <!-- Card 1: Dokumen Manual -->
                <article class="benefit-card">
                    <span class="icon-circle" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </span>
                    <div>
                        <h3>Administrasi Masih Manual</h3>
                        <p>Pengajuan surat masih mengandalkan kertas fisik dan warga harus bolak-balik ke rumah RT hanya untuk mengecek status.</p>
                    </div>
                </article>

                <!-- Card 2: Keuangan Kurang Terbuka (SVG Bersih & Presisi) -->
                <article class="benefit-card">
                    <span class="icon-circle" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                            <line x1="2" y1="10" x2="22" y2="10"/>
                            <circle cx="12" cy="15" r="2"/>
                        </svg>
                    </span>
                    <div>
                        <h3>Keuangan Kurang Terbuka</h3>
                        <p>Laporan pemasukan, kas operasional, dan iuran lingkungan sulit dipantau warga secara berkala dan akuntabel.</p>
                    </div>
                </article>

                <!-- Card 3: Informasi Terlewat (SVG Bersih & Presisi) -->
                <article class="benefit-card">
                    <span class="icon-circle" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 5 6 9H2v6h4l5 4V5Z"/>
                            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
                            <path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
                        </svg>
                    </span>
                    <div>
                        <h3>Informasi Cepat Tertimbun</h3>
                        <p>Pengumuman dan agenda warga sering tenggelam di grup percakapan, membuat partisipasi kegiatan warga menurun.</p>
                    </div>
                </article>
            </div>
        </section>

        <!-- SECTION 7 FITUR RESMI (PRD & AGENTS.MD) -->
        <section class="platform-section container" id="fitur">
            <div class="center-heading">
                <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span>7 Layanan Terintegrasi</p>
                <h2 class="section-title">Semua Kebutuhan Lingkungan<br>Dalam <span class="accent">Satu Platform</span></h2>
                <p>Tujuh modul fungsional resmi yang dirancang khusus untuk memenuhi tata kelola RT/RW yang transparan, aman, dan mudah digunakan.</p>
            </div>
            
            <div class="feature-grid">
                <!-- 1. Pengajuan Surat (Core 1 - Col 4) -->
                <article class="feature-card col-4" data-feature-card="surat">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                    <h3>Pengajuan Surat Otomatis</h3>
                    <p>6 jenis surat resmi dengan generator nomor surat otomatis, QR code validasi, dan unduh PDF berstempel elektronik.</p>
                    <button type="button" class="feature-card-btn" data-feature-trigger="surat">Pelajari Detail &rarr;</button>
                </article>

                <!-- 2. Ruang Komunitas (Core 2 - Col 4) -->
                <article class="feature-card col-4" data-feature-card="komunitas">
                    <span class="feature-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                    <h3>Ruang Komunitas</h3>
                    <p>Pengumuman resmi berjangka waktu, Chat Bebas real-time (Laravel Reverb), dan Forum Warga berjenjang RT/RW.</p>
                    <button type="button" class="feature-card-btn" data-feature-trigger="komunitas">Pelajari Detail &rarr;</button>
                </article>

                <!-- 3. Transparansi Kas RT (Core 3 - Col 4) -->
                <article class="feature-card col-4" data-feature-card="kas">
                    <span class="feature-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                    <h3>Transparansi Anggaran</h3>
                    <p>Pantau saldo kas lingkungan, riwayat transaksi masuk/keluar, bukti kuitansi digital, dan grafik keuangan 6 bulan.</p>
                    <button type="button" class="feature-card-btn" data-feature-trigger="kas">Pelajari Detail &rarr;</button>
                </article>

                <!-- 4. Audit Trail (Col 3) -->
                <article class="feature-card col-3" data-feature-card="audit">
                    <span class="feature-icon purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg></span>
                    <h3>Audit Trail System</h3>
                    <p>Meja audit akuntabilitas: seluruh tindakan penting pengurus tercatat permanen dengan jejak waktu, aktor, dan alasan resmi.</p>
                    <button type="button" class="feature-card-btn" data-feature-trigger="audit">Pelajari Detail &rarr;</button>
                </article>

                <!-- 5. UMKM Warga (Col 3) -->
                <article class="feature-card col-3" data-feature-card="umkm">
                    <span class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></span>
                    <h3>Ekonomi Lokal &amp; UMKM</h3>
                    <p>Etalase produk dan jasa warga sekitar se-RW dengan penghubung langsung ke WhatsApp tanpa potongan komisi.</p>
                    <button type="button" class="feature-card-btn" data-feature-trigger="umkm">Pelajari Detail &rarr;</button>
                </article>

                <!-- 6. Kalender Kegiatan (Col 3) -->
                <article class="feature-card col-3" data-feature-card="kalender">
                    <span class="feature-icon indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    <h3>Kalender Kegiatan</h3>
                    <p>Agenda rapat, kerja bakti, posyandu, dan ronda malam tersinkronisasi otomatis dengan panel agenda terdekat.</p>
                    <button type="button" class="feature-card-btn" data-feature-trigger="kalender">Pelajari Detail &rarr;</button>
                </article>

                <!-- 7. Galeri Kegiatan (Col 3) -->
                <article class="feature-card col-3" data-feature-card="galeri">
                    <span class="feature-icon rose"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span>
                    <h3>Galeri Kegiatan Warga</h3>
                    <p>Dokumentasi foto kegiatan lingkungan dalam album terorganisir dengan tampilan lightbox interaktif dan aman.</p>
                    <button type="button" class="feature-card-btn" data-feature-trigger="galeri">Pelajari Detail &rarr;</button>
                </article>
            </div>
        </section>

        <!-- SPILL FITUR KHUSUS: VERIFIKASI SURAT PUBLIK -->
        <section class="verify-banner-section container">
            <div class="verify-banner">
                <div class="verify-banner-copy">
                    <p class="eyebrow eyebrow-light"><span class="eyebrow-dot" aria-hidden="true"></span>Standar Keabsahan &amp; Anti-Pemalsuan</p>
                    <h2>Verifikasi Dokumen Resmi Bebas Calo &amp; Anti-Manipulasi.</h2>
                    <p>Setiap surat yang diterbitkan melalui Warga Digital dibekali stempel elektronik dan kode unik QR Code. Pihak kelurahan, bank, atau instansi luar dapat langsung mengecek validitas dokumen secara publik tanpa login.</p>
                    <a class="button button-primary" href="{{ route('verifikasi.index') }}">
                        <span>Coba Portal Verifikasi Surat</span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="verify-card-mockup" aria-hidden="true">
                    <div class="verify-mockup-header">
                        <span class="verify-mockup-type">SURAT KETERANGAN USAHA</span>
                        <span class="verify-mockup-badge">&check; SAH &amp; TERVALIDASI</span>
                    </div>
                    <div class="verify-mockup-code">
                        <span>KODE: SKU-202610-0042</span>
                        <span class="verify-mockup-status">AKTIF</span>
                    </div>
                    <div class="verify-mockup-meta">
                        <div>Diterbitkan oleh: <strong>Ketua RT 05 / RW 03</strong></div>
                        <div>Prinsip Privasi: <em>Minimum Necessary Disclosure (NIK Terlindungi)</em></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION CARA KERJA - BERSIH TANPA STRIP PELANGI AI -->
        <section class="experience-section container" id="cara-kerja">
            <div class="experience-panel">
                <div class="experience-header">
                    <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span>Alur Layanan Mandiri</p>
                    <h2 class="section-title">Semua kebutuhan warga,<br>lebih mudah dari <span class="accent">satu pintu.</span></h2>
                    <p class="experience-intro">Warga cukup memilih layanan yang dibutuhkan dari rumah. Pengurus memeriksa secara terstruktur, dan status dokumen terpantau transparan.</p>
                </div>

                <div class="experience-journey">
                    <!-- Step 01 -->
                    <article class="exp-card">
                        <div class="exp-card-header">
                            <span class="exp-step-badge">01</span>
                            <span class="exp-step-tag">Akses Mandiri</span>
                        </div>
                        <div class="exp-card-copy">
                            <h3>Masuk ke portal</h3>
                            <p>Warga yang telah terdaftar memasukkan identitas NIK dan membuat kata sandi pertama kali dengan aman.</p>
                        </div>
                        <div class="exp-mini-ui">
                            <div class="exp-ui-topbar">
                                <div class="exp-ui-channel">
                                    <span class="exp-dot-pulse"></span>
                                    <span>Portal RT 05</span>
                                </div>
                                <span class="exp-ui-badge">Terdaftar</span>
                            </div>
                            <div class="exp-field-row">
                                <span class="exp-field-lbl">Identitas Warga (NIK)</span>
                                <div class="exp-field-box">
                                    <span>3273 •••• •••• 0001</span>
                                    <span class="exp-field-verify">✓ Valid</span>
                                </div>
                            </div>
                            <div class="exp-action-btn">
                                <span>Aktivasi Akun Baru</span>
                                <span aria-hidden="true">&rarr;</span>
                            </div>
                        </div>
                    </article>

                    <!-- Step 02 -->
                    <article class="exp-card">
                        <div class="exp-card-header">
                            <span class="exp-step-badge">02</span>
                            <span class="exp-step-tag">Pilih Layanan</span>
                        </div>
                        <div class="exp-card-copy">
                            <h3>Pilih kebutuhan</h3>
                            <p>Mulai dari pengajuan 6 jenis surat, melihat keterbukaan kas RT, hingga belanja di etalase UMKM warga.</p>
                        </div>
                        <div class="exp-mini-ui">
                            <div class="exp-ui-topbar">
                                <div class="exp-ui-channel">
                                    <span class="exp-dot-pulse amber"></span>
                                    <span>Layanan Mandiri</span>
                                </div>
                                <span class="exp-ui-badge amber">1 Ketuk</span>
                            </div>
                            <div class="exp-menu-item is-active">
                                <div class="exp-menu-left">
                                    <span class="exp-menu-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    </span>
                                    <div>
                                        <strong class="exp-menu-name">Surat Pengantar RT</strong>
                                        <span class="exp-menu-sub">SKU / Domisili / SKTM</span>
                                    </div>
                                </div>
                                <span class="exp-check-pill">Pilih</span>
                            </div>
                            <div class="exp-menu-item">
                                <div class="exp-menu-left">
                                    <span class="exp-menu-icon green" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                    </span>
                                    <div>
                                        <strong class="exp-menu-name">Transparansi Kas</strong>
                                        <span class="exp-menu-sub">Cek Mutasi &amp; Iuran</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Step 03 -->
                    <article class="exp-card">
                        <div class="exp-card-header">
                            <span class="exp-step-badge">03</span>
                            <span class="exp-step-tag">Pantau Status</span>
                        </div>
                        <div class="exp-card-copy">
                            <h3>Proses berjalan jelas</h3>
                            <p>Pengajuan diperiksa oleh Ketua RT dan Sekretaris. Jika berkas kurang, ada catatan panduan yang jelas.</p>
                        </div>
                        <div class="exp-mini-ui">
                            <div class="exp-ui-topbar">
                                <div class="exp-ui-channel">
                                    <span class="exp-dot-pulse amber"></span>
                                    <span>Status Pengajuan</span>
                                </div>
                                <span class="exp-ui-badge amber">Diproses</span>
                            </div>
                            <div class="exp-stepper">
                                <div class="exp-step-node is-done">
                                    <span class="exp-node-bullet">✓</span>
                                    <div class="exp-node-text">
                                        <strong>Diajukan Warga</strong>
                                        <small>Berkas lengkap online</small>
                                    </div>
                                </div>
                                <div class="exp-step-node is-active">
                                    <span class="exp-node-bullet">●</span>
                                    <div class="exp-node-text">
                                        <strong>Verifikasi Pengurus</strong>
                                        <small>Ditinjau Ketua RT secara digital</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Step 04 -->
                    <article class="exp-card">
                        <div class="exp-card-header">
                            <span class="exp-step-badge">04</span>
                            <span class="exp-step-tag">Hasil Instan</span>
                        </div>
                        <div class="exp-card-copy">
                            <h3>Selesai tanpa antre</h3>
                            <p>Setelah disetujui, surat berstempel digital dan QR Code resmi langsung dapat diunduh dalam format PDF.</p>
                        </div>
                        <div class="exp-mini-ui">
                            <div class="exp-ui-topbar">
                                <div class="exp-ui-channel">
                                    <span class="exp-dot-pulse"></span>
                                    <span>Dokumen Siap</span>
                                </div>
                                <span class="exp-ui-badge">✓ Disetujui</span>
                            </div>
                            <div class="exp-doc-box">
                                <div class="exp-doc-icon-wrap">PDF</div>
                                <div>
                                    <strong class="exp-doc-title">Surat_Keterangan.pdf</strong>
                                    <span class="exp-doc-meta">No: 042/RT-05/X/2026</span>
                                </div>
                            </div>
                            <div class="exp-download-btn">
                                <span>Unduh PDF Resmi</span>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- DIRANCANG UNTUK BERTUMBUH -->
        <section class="growth-section container">
            <div class="growth-content">
                <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span>Arsitektur Multi-Tenant</p>
                <h2 class="section-title">Mulai dari satu RT, tumbuh <span class="accent">bersama</span> satu RW.</h2>
                <p class="growth-intro">Arsitektur Warga Digital dibangun dengan multi-tenant database yang fleksibel, memisahkan data antarranting RT namun tetap terhubung di bawah supervisi koordinasi RW.</p>
                <div class="growth-steps">
                    <article class="growth-card">
                        <small>Fase 1 · Tingkat RT</small>
                        <h3>Satu RT Mandiri</h3>
                        <p>Kelola surat pengantar, pembukuan kas bulanan, pengumuman, dan forum internal warga RT secara otonom.</p>
                    </article>
                    <span class="growth-arrow" aria-hidden="true">&rarr;</span>
                    <article class="growth-card">
                        <small>Fase 2 · Tingkat RW</small>
                        <h3>Satu RW Terpadu</h3>
                        <p>Ketua RW memiliki supervisi pengawasan arus kas lintas-RT, siaran pengumuman serentak, dan etalase UMKM bersama.</p>
                    </article>
                    <span class="growth-arrow" aria-hidden="true">&rarr;</span>
                    <article class="growth-card">
                        <small>Fase 3 · Multi Wilayah</small>
                        <h3>Ekspansi Kawasan</h3>
                        <p>Struktur siap dikembangkan untuk skala wilayah perumahan, kelurahan, dan agregasi statistik kependudukan yang lebih luas.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- GALERI PERAN PENGGUNA - FOTO LOKAL OFFLINE AMAN -->
        <section class="roles-section container" id="peran">
            <div class="roles-heading">
                <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span>Tata Kelola Peran &amp; Wewenang</p>
                <h2 class="section-title">Satu sistem, tugas yang<br>terbagi <span class="accent">jelas &amp; tertib.</span></h2>
            </div>
            
            <div class="role-gallery" aria-label="Daftar Peran Pengguna">
                <!-- 1. Warga -->
                <article class="role-card" data-role="warga" tabindex="0" role="button" aria-expanded="false">
                    <img src="{{ asset('assets/roles/warga.jpg') }}" alt="Warga Lingkungan" loading="lazy">
                    <div class="role-front">
                        <div class="role-name">Warga</div>
                        <span class="role-hint">Lihat tanggung jawab &rarr;</span>
                    </div>
                    <div class="role-detail">
                        <div class="role-detail-header">
                            <div>
                                <span class="role-badge">Akses Mandiri</span>
                                <h3 class="role-detail-title">Warga Terdaftar</h3>
                            </div>
                            <button type="button" class="role-close-btn" aria-label="Tutup detail peran Warga">&times;</button>
                        </div>
                        <p class="role-desc">Pusat pelayanan mandiri bagi warga untuk mengurus keperluan surat, memantau kas, dan berdiskusi sehat.</p>
                        <div>
                            <span class="role-duties-label">Fitur Utama:</span>
                            <ul class="role-duties-list">
                                <li>Pengajuan surat keterangan online dari HP.</li>
                                <li>Pantau arus kas RT real-time tanpa kecurigaan.</li>
                                <li>Chat Bebas &amp; Forum warga beretika.</li>
                                <li>Buka toko produk/jasa di etalase UMKM.</li>
                            </ul>
                        </div>
                    </div>
                </article>

                <!-- 2. Ketua RT -->
                <article class="role-card" data-role="ketua_rt" tabindex="0" role="button" aria-expanded="false">
                    <img src="{{ asset('assets/roles/ketua_rt.jpg') }}" alt="Ketua RT" loading="lazy">
                    <div class="role-front">
                        <div class="role-name">Ketua RT</div>
                        <span class="role-hint">Lihat tanggung jawab &rarr;</span>
                    </div>
                    <div class="role-detail">
                        <div class="role-detail-header">
                            <div>
                                <span class="role-badge">Pimpinan Lingkungan</span>
                                <h3 class="role-detail-title">Ketua RT</h3>
                            </div>
                            <button type="button" class="role-close-btn" aria-label="Tutup detail peran Ketua RT">&times;</button>
                        </div>
                        <p class="role-desc">Pimpinan lingkungan pemegang wewenang persetujuan surat, verifikasi UMKM, dan moderasi komunitas.</p>
                        <div>
                            <span class="role-duties-label">Fitur Utama:</span>
                            <ul class="role-duties-list">
                                <li>Persetujuan (Approve/Reject) surat digital.</li>
                                <li>Kurasi pendaftaran etalase UMKM warga.</li>
                                <li>Penerbitan pengumuman resmi berkala.</li>
                                <li>Akses Meja Audit &amp; monitoring kas RT.</li>
                            </ul>
                        </div>
                    </div>
                </article>

                <!-- 3. Sekretaris -->
                <article class="role-card" data-role="sekretaris" tabindex="0" role="button" aria-expanded="false">
                    <img src="{{ asset('assets/roles/sekretaris.jpg') }}" alt="Sekretaris RT" loading="lazy">
                    <div class="role-front">
                        <div class="role-name">Sekretaris</div>
                        <span class="role-hint">Lihat tanggung jawab &rarr;</span>
                    </div>
                    <div class="role-detail">
                        <div class="role-detail-header">
                            <div>
                                <span class="role-badge">Tata Usaha</span>
                                <h3 class="role-detail-title">Sekretaris RT</h3>
                            </div>
                            <button type="button" class="role-close-btn" aria-label="Tutup detail peran Sekretaris">&times;</button>
                        </div>
                        <p class="role-desc">Pengelola arsip persuratan, tata kelola data warga, agenda kegiatan, dan dokumentasi lingkungan.</p>
                        <div>
                            <span class="role-duties-label">Fitur Utama:</span>
                            <ul class="role-duties-list">
                                <li>Pemeriksaan berkas &amp; penomoran surat otomatis.</li>
                                <li>Pengelolaan data kependudukan NIK warga.</li>
                                <li>Pencatatan kalender agenda kegiatan RT.</li>
                                <li>Unggah album dokumentasi galeri warga.</li>
                            </ul>
                        </div>
                    </div>
                </article>

                <!-- 4. Bendahara -->
                <article class="role-card" data-role="bendahara" tabindex="0" role="button" aria-expanded="false">
                    <img src="{{ asset('assets/roles/bendahara.jpg') }}" alt="Bendahara RT" loading="lazy">
                    <div class="role-front">
                        <div class="role-name">Bendahara</div>
                        <span class="role-hint">Lihat tanggung jawab &rarr;</span>
                    </div>
                    <div class="role-detail">
                        <div class="role-detail-header">
                            <div>
                                <span class="role-badge">Tata Keuangan</span>
                                <h3 class="role-detail-title">Bendahara RT</h3>
                            </div>
                            <button type="button" class="role-close-btn" aria-label="Tutup detail peran Bendahara">&times;</button>
                        </div>
                        <p class="role-desc">Penanggung jawab pencatatan kas kasir terbuka dengan mekanisme koreksi append-only tanpa manipulasi.</p>
                        <div>
                            <span class="role-duties-label">Fitur Utama:</span>
                            <ul class="role-duties-list">
                                <li>Pencatatan kas masuk &amp; pengeluaran berkala.</li>
                                <li>Unggah bukti nota/kuitansi transaksi sah.</li>
                                <li>Koreksi transaksi berjejak audit trail.</li>
                                <li>Penyajian grafik perbandingan keuangan.</li>
                            </ul>
                        </div>
                    </div>
                </article>

                <!-- 5. Ketua RW -->
                <article class="role-card" data-role="ketua_rw" tabindex="0" role="button" aria-expanded="false">
                    <img src="{{ asset('assets/roles/ketua_rw.jpg') }}" alt="Ketua RW" loading="lazy">
                    <div class="role-front">
                        <div class="role-name">Ketua RW</div>
                        <span class="role-hint">Lihat tanggung jawab &rarr;</span>
                    </div>
                    <div class="role-detail">
                        <div class="role-detail-header">
                            <div>
                                <span class="role-badge">Supervisi Wilayah</span>
                                <h3 class="role-detail-title">Ketua RW</h3>
                            </div>
                            <button type="button" class="role-close-btn" aria-label="Tutup detail peran Ketua RW">&times;</button>
                        </div>
                        <p class="role-desc">Koordinator lintas-RT dalam lingkup RW untuk supervisi anggaran kas, pengumuman kawasan, dan etalase UMKM.</p>
                        <div>
                            <span class="role-duties-label">Fitur Utama:</span>
                            <ul class="role-duties-list">
                                <li>Supervisi read-only kas seluruh RT binaan.</li>
                                <li>Siaran pengumuman serentak tingkat RW.</li>
                                <li>Moderasi forum kawasan &amp; takedown UMKM berjenjang.</li>
                                <li>Pengawasan rekonsiliasi keaktifan RT.</li>
                            </ul>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <!-- FAQ ACCORDION -->
        <section class="faq-section container" id="faq">
            <div class="center-heading">
                <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span>Pusat Tanya Jawab</p>
                <h2 class="section-title">Jawaban Jelas untuk<br>Kebutuhan <span class="accent">Warga &amp; Pengurus</span></h2>
                <p>Pertanyaan umum seputar penggunaan, keamanan data, dan kepatuhan sistem Warga Digital.</p>
            </div>

            <div class="faq-accordion">
                <!-- 01 -->
                <article class="faq-item">
                    <button type="button" class="faq-trigger" aria-expanded="false">
                        <span class="faq-number">01</span>
                        <span class="faq-question">Apakah warga masih harus datang ke rumah Ketua RT untuk mengurus surat?</span>
                        <span class="faq-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer-wrapper">
                        <div class="faq-answer-content">
                            <p>Tidak perlu lagi. Pengajuan surat keterangan (seperti Domisili, SKU, SKTM, dll) dapat diajukan langsung dari ponsel warga. Pengurus memeriksa dan menyetujui secara digital, dan dokumen PDF bertanda tangan sah serta ber-QR Code dapat langsung diunduh.</p>
                        </div>
                    </div>
                </article>

                <!-- 02 -->
                <article class="faq-item">
                    <button type="button" class="faq-trigger" aria-expanded="false">
                        <span class="faq-number">02</span>
                        <span class="faq-question">Bagaimana keamanan data kependudukan dan NIK warga?</span>
                        <span class="faq-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer-wrapper">
                        <div class="faq-answer-content">
                            <p>Sangat aman. Warga Digital mematuhi <strong>UU Pelindungan Data Pribadi (UU PDP No. 27/2022)</strong> dengan menerapkan prinsip <em>Minimum Necessary Disclosure</em>. NIK hanya digunakan sebagai jangkar identitas di backend dan tidak pernah dikirim ke respon JSON publik, log aplikasi, atau stempel QR code dokumen.</p>
                        </div>
                    </div>
                </article>

                <!-- 03 -->
                <article class="faq-item">
                    <button type="button" class="faq-trigger" aria-expanded="false">
                        <span class="faq-number">03</span>
                        <span class="faq-question">Apakah platform ini sulit dipakai oleh warga lanjut usia (lansia)?</span>
                        <span class="faq-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer-wrapper">
                        <div class="faq-answer-content">
                            <p>Antarmuka Warga Digital dirancang dengan kontras warna tinggi, teks berbahasa Indonesia sederhana tanpa istilah teknis asing, serta tombol besar yang ramah sentuhan. Selain itu, anggota keluarga dalam satu Kartu Keluarga dapat membantu pengurusan.</p>
                        </div>
                    </div>
                </article>

                <!-- 04 -->
                <article class="faq-item">
                    <button type="button" class="faq-trigger" aria-expanded="false">
                        <span class="faq-number">04</span>
                        <span class="faq-question">Bagaimana transparansi kas mencegah kecurigaan atau manipulasi dana?</span>
                        <span class="faq-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer-wrapper">
                        <div class="faq-answer-content">
                            <p>Setiap transaksi kas wajib menyertakan bukti nota/kuitansi digital. Sistem menerapkan mekanisme koreksi <em>append-only</em> berantai: transaksi lama tidak pernah dihapus diam-diam, melainkan ditandai dengan riwayat koreksi dan tercatat otomatis di Meja Audit Akuntabilitas.</p>
                        </div>
                    </div>
                </article>

                <!-- 05 -->
                <article class="faq-item">
                    <button type="button" class="faq-trigger" aria-expanded="false">
                        <span class="faq-number">05</span>
                        <span class="faq-question">Bagaimana instansi luar (kelurahan, bank, kampus) memverifikasi surat warga?</span>
                        <span class="faq-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer-wrapper">
                        <div class="faq-answer-content">
                            <p>Instansi luar cukup memindai QR Code di surat PDF atau memasukkan kode verifikasi dokumen di menu <strong>Verifikasi Surat</strong> publik tanpa perlu membuat akun atau login. Sistem akan menampilkan status keaslian, nomor surat, tanggal pengesahan, dan nama pengurus penerbit.</p>
                        </div>
                    </div>
                </article>

                <!-- 06 -->
                <article class="faq-item">
                    <button type="button" class="faq-trigger" aria-expanded="false">
                        <span class="faq-number">06</span>
                        <span class="faq-question">Apakah ada potongan biaya atau komisi untuk promosi usaha UMKM warga?</span>
                        <span class="faq-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer-wrapper">
                        <div class="faq-answer-content">
                            <p>Sama sekali bebas biaya dan tanpa potongan komisi (0%). Etalase UMKM Warga menghubungkan calon pembeli langsung ke nomor WhatsApp pemilik usaha untuk memajukan ekonomi warga tetangga sekitar secara organik.</p>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <!-- CTA FINAL -->
        <section class="cta-section container">
            <div class="cta-panel">
                <div class="cta-copy">
                    <h2>Siap Mewujudkan Tata Kelola Lingkungan yang Modern &amp; Guyub?</h2>
                    <p>Mulai dari RT Anda hari ini. Nikmati administrasi yang tertib, keterbukaan anggaran yang menenangkan, dan komunikasi warga yang terhubung erat.</p>
                    <div class="cta-buttons">
                        <a class="button button-primary" href="{{ route('login') }}">
                            <span>Masuk ke Portal Warga</span>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <a class="button button-quiet" href="{{ route('first-time.form') }}">Aktivasi Sandi Akun Baru</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER BERSIH & PROFESIONAL (TANPA FAKE MAP) -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-main">
                <div class="footer-brand">
                    <a class="brand" href="#beranda">
                        <span class="brand-mark" aria-hidden="true">
                            <img src="{{ asset('assets/logo.png') }}" alt="Logo Warga Digital" width="40" height="40">
                        </span>
                        <span class="brand-name">Warga<span>Digital</span></span>
                    </a>
                    <p>Platform tata kelola administrasi, transparansi keuangan kas, dan komunikasi terpadu rukun tetangga &amp; rukun warga.</p>
                </div>

                <div class="footer-links">
                    <h3>Navigasi</h3>
                    <a href="#beranda">Beranda</a>
                    <a href="#tentang">Masalah &amp; Solusi</a>
                    <a href="#fitur">7 Fitur Resmi</a>
                    <a href="#cara-kerja">Alur Kerja</a>
                    <a href="#peran">Peran Pengguna</a>
                </div>

                <div class="footer-links">
                    <h3>Layanan</h3>
                    <a href="{{ route('login') }}">Portal Masuk Warga</a>
                    <a href="{{ route('first-time.form') }}">Aktivasi Akun Mandiri</a>
                    <a href="{{ route('verifikasi.index') }}">Verifikasi Surat Publik</a>
                    <a href="#faq">Pusat Tanya Jawab (FAQ)</a>
                </div>

                <div class="footer-links">
                    <h3>Kepatuhan &amp; Tim</h3>
                    <a href="#beranda">SATU CREANOVA 2026</a>
                    <a href="#beranda">Tim CodeRanger</a>
                    <a href="#beranda">Kepatuhan UU PDP No. 27/2022</a>
                    <a href="#beranda">Framework Laravel 11</a>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} Warga Digital — Dibuat untuk babak Final SATU CREANOVA 2026 oleh Tim CodeRanger.</span>
                <span>Dirancang untuk lingkungan yang guyub, transparan, dan inklusif.</span>
            </div>
        </div>
    </footer>

    <!-- FEATURE DETAIL SIDE PANEL (OFF-CANVAS DRAWER) -->
    <div id="feature-drawer-overlay" class="drawer-overlay" aria-hidden="true"></div>
    <aside id="feature-drawer" class="feature-drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-title" aria-hidden="true" tabindex="-1">
        <div class="drawer-header">
            <div class="drawer-header-meta">
                <span class="drawer-icon-wrap" id="drawer-icon-wrap" aria-hidden="true">
                    <!-- Icon SVG Dinamis -->
                </span>
                <div>
                    <span class="drawer-badge" id="drawer-badge">Fitur Resmi Warga Digital</span>
                    <h2 class="drawer-title" id="drawer-title">Nama Fitur</h2>
                </div>
            </div>
            <button type="button" class="drawer-close-btn" id="drawer-close-btn" aria-label="Tutup Detail Fitur">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="drawer-body" id="drawer-body">
            <div class="drawer-desc-box">
                <p class="drawer-desc" id="drawer-desc">Deskripsi fitur...</p>
            </div>

            <!-- Cara Kerja Modul -->
            <div class="drawer-section">
                <h3 class="drawer-section-title">
                    <span class="section-indicator" aria-hidden="true"></span>
                    <span>Bagaimana Cara Kerjanya?</span>
                </h3>
                <div class="drawer-steps-list" id="drawer-steps-list"></div>
            </div>

            <!-- Manfaat Utama -->
            <div class="drawer-section">
                <h3 class="drawer-section-title">
                    <span class="section-indicator" aria-hidden="true"></span>
                    <span>Manfaat Bagi Warga &amp; Pengurus</span>
                </h3>
                <ul class="drawer-benefits-list" id="drawer-benefits-list"></ul>
            </div>
        </div>

        <div class="drawer-footer">
            <div class="drawer-footer-note">
                <span class="pulse-dot" aria-hidden="true"></span>
                <span>Tersedia langsung di dalam portal Warga Digital</span>
            </div>
            <div class="drawer-footer-actions">
                <button type="button" class="button button-quiet drawer-dismiss-btn drawer-btn-dismiss">Tutup</button>
                <a href="{{ route('login') }}" class="button button-primary drawer-btn-cta">Masuk Portal &rarr;</a>
            </div>
        </div>
    </aside>

</div>

<!-- INTERACTIVE SCRIPTS -->
<script>
    (function () {
        // Sticky Header scroll elevation & active link highlighting
        const header = document.querySelector('.site-header');
        const navLinks = document.querySelectorAll('.nav-links a[href^="#"]');
        const sections = document.querySelectorAll('main section[id]');

        function handleScroll() {
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            if (header) {
                if (scrollTop > 20) {
                    header.classList.add('is-scrolled');
                } else {
                    header.classList.remove('is-scrolled');
                }
            }

            let currentId = '';
            sections.forEach(sec => {
                const top = sec.offsetTop - 120;
                const height = sec.offsetHeight;
                if (scrollTop >= top && scrollTop < top + height) {
                    currentId = sec.getAttribute('id');
                }
            });

            if (currentId) {
                navLinks.forEach(link => {
                    const targetId = link.getAttribute('href').replace('#', '');
                    if (targetId === currentId) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            }
        }

        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();

        // Interactive Role Cards
        const roleGallery = document.querySelector('.role-gallery');
        const roleCards = document.querySelectorAll('.role-card');

        if (roleGallery && roleCards.length > 0) {
            function closeAllRoleCards() {
                roleCards.forEach(c => {
                    c.classList.remove('is-active');
                    c.setAttribute('aria-expanded', 'false');
                });
            }

            roleCards.forEach(card => {
                card.addEventListener('click', (e) => {
                    if (e.target.closest('.role-close-btn')) {
                        e.stopPropagation();
                        card.classList.remove('is-active');
                        card.setAttribute('aria-expanded', 'false');
                        return;
                    }
                    if (e.target.closest('.role-detail')) return;

                    const wasActive = card.classList.contains('is-active');
                    closeAllRoleCards();
                    if (!wasActive) {
                        card.classList.add('is-active');
                        card.setAttribute('aria-expanded', 'true');
                    }
                });
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.role-gallery')) closeAllRoleCards();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeAllRoleCards();
            });
        }

        // FAQ Accordion
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            const trigger = item.querySelector('.faq-trigger');
            if (!trigger) return;
            trigger.addEventListener('click', () => {
                const isOpen = item.classList.contains('is-open');
                faqItems.forEach(other => {
                    other.classList.remove('is-open');
                    const otherTrig = other.querySelector('.faq-trigger');
                    if (otherTrig) otherTrig.setAttribute('aria-expanded', 'false');
                });
                if (!isOpen) {
                    item.classList.add('is-open');
                    trigger.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // 7 Fitur Resmi Drawer Data
        const featureData = {
            surat: {
                title: 'Pengajuan Surat Otomatis',
                badge: 'Pelayanan Administrasi',
                color: 'amber',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
                desc: 'Layanan mandiri pengajuan 6 jenis surat keterangan resmi (SKD, SKU, SKTM, SPKK, SKL, SKKm) tanpa perlu antre ke rumah RT.',
                steps: [
                    { name: 'Pilih jenis surat & lengkapi data', desc: 'Isi formulir kebutuhan surat dan unggah lampiran syarat yang diminta.' },
                    { name: 'Pemeriksaan pengurus RT', desc: 'Ketua RT & Sekretaris memeriksa kelengkapan berkas secara digital.' },
                    { name: 'Penerbitan nomor surat otomatis', desc: 'Sistem men-generate format penomoran resmi standar RT/RW.' },
                    { name: 'Unduh PDF sah berstempel digital', desc: 'Dokumen terbit dengan QR Code unik yang dapat diverifikasi publik.' }
                ],
                benefits: [
                    'Menghemat waktu warga tanpa harus bolak-balik',
                    'Format dokumen rapi berstandar administrasi resmi',
                    'QR Code anti-pemalsuan tervalidasi publik',
                    'Jejak riwayat arsip tersimpan aman'
                ]
            },
            komunitas: {
                title: 'Ruang Komunitas',
                badge: 'Komunikasi & Aspirasi',
                color: 'blue',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
                desc: 'Satu wadah komunikasi terpadu yang memadukan Pengumuman resmi, Chat Bebas real-time (Laravel Reverb), dan Forum Warga berjenjang.',
                steps: [
                    { name: 'Pengumuman resmi bersiklus', desc: 'Pengurus menyiarkan warta dengan tanggal kedaluwarsa dan pembaruan linear.' },
                    { name: 'Chat Bebas real-time', desc: 'Interaksi instan antarwarga dengan perlindungan XSS dan isolasi kanal RT/RW.' },
                    { name: 'Forum Warga terarah', desc: 'Wadah musyawarah aspirasi bertopik dengan moderasi pengurus (Pin/Tutup/Hapus).' }
                ],
                benefits: [
                    'Warta penting tidak tertimbun obrolan santai',
                    'Komunikasi instan tanpa perlu grup chat pihak ketiga',
                    'Aspirasi warga tercatat dan dapat ditindaklanjuti',
                    'Moderasi tertib berjejak alasan resmi'
                ]
            },
            kas: {
                title: 'Transparansi Anggaran Kas',
                badge: 'Keterbukaan Finansial',
                color: 'green',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
                desc: 'Keterbukaan pembukuan keuangan RT yang dapat dipantau langsung oleh seluruh warga dengan grafik mutasi 6 bulan.',
                steps: [
                    { name: 'Pencatatan kas masuk & keluar', desc: 'Bendahara mencatat iuran, sumbangan, atau pengeluaran operasional.' },
                    { name: 'Unggah bukti sah', desc: 'Setiap transaksi dilengkapi nota/kuitansi digital untuk verifikasi warga.' },
                    { name: 'Koreksi append-only', desc: 'Koreksi data tidak menghapus riwayat lama guna menjaga keaslian forensik.' },
                    { name: 'Supervisi Ketua RW', desc: 'Pimpinan RW dapat memonitor perbandingan kesehatan kas antarranting RT.' }
                ],
                benefits: [
                    'Menghilangkan saling curiga antara warga dan pengurus',
                    'Laporan visual grafik arus kas yang mudah dipahami',
                    'Bukti pengeluaran terbuka dan dapat diakses kapan saja',
                    'Akuntabilitas pembukuan terstandar'
                ]
            },
            audit: {
                title: 'Audit Trail System-Wide',
                badge: 'Akuntabilitas & Integritas',
                color: 'purple',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>',
                desc: 'Meja audit forensik pengurus yang mencatat seluruh aksi krusial sistem demi menjaga integritas data dan kepatuhan regulasi.',
                steps: [
                    { name: 'Aksi krusial terjadi', desc: 'Aksi persetujuan surat, koreksi kas, moderasi forum, atau takedown UMKM.' },
                    { name: 'Pencatatan otomatis', desc: 'Sistem mencatat nama aktor, jabatan canonical, IP address, waktu, dan alasan.' },
                    { name: 'Penyelidikan forensik', desc: 'Pengurus berwenang dapat memfilter dan meninjau selisih data lama vs baru.' }
                ],
                benefits: [
                    'Mencegah penyalahgunaan wewenang secara diam-diam',
                    'Transparansi alasan resmi pada setiap tindakan pengurus',
                    'Kepatuhan terhadap standar rekam jejak digital'
                ]
            },
            umkm: {
                title: 'Ekonomi Lokal & UMKM',
                badge: 'Pemberdayaan Warga',
                color: 'amber',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
                desc: 'Etalase promosi barang dan jasa antarwarga se-RW dengan penghubung WhatsApp instan bebas potongan biaya.',
                steps: [
                    { name: 'Pendaftaran usaha mandiri', desc: 'Warga mendaftarkan produk, jasa, deskripsi, dan nomor kontak WhatsApp.' },
                    { name: 'Kurasi pengurus RT', desc: 'Pengurus memeriksa kelayakan usaha sebelum listing tayang ke warga.' },
                    { name: 'Dukungan tetangga', desc: 'Warga menemukan kebutuhan harian di sekitar rumah tanpa ongkir mahal.' },
                    { name: 'Hubungi via WhatsApp', desc: 'Pembeli terhubung langsung ke penjual tanpa perantara payment gateway.' }
                ],
                benefits: [
                    '0% potongan komisi untuk pedagang kecil',
                    'Membangkitkan perputaran ekonomi tetangga',
                    'Kontak langsung WhatsApp yang akrab dan fleksibel',
                    'Takedown berjenjang jika melanggar ketertiban'
                ]
            },
            kalender: {
                title: 'Kalender Kegiatan Lingkungan',
                badge: 'Koordinasi Jadwal',
                color: 'indigo',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                desc: 'Agenda interaktif terpadu untuk menyelaraskan jadwal rapat, kerja bakti, posyandu, dan ronda malam antarranting RT & RW.',
                steps: [
                    { name: 'Penjadwalan kegiatan', desc: 'Pengurus RT/RW menentukan tanggal, waktu, lokasi, dan kategori agenda.' },
                    { name: 'Sinkronisasi otomatis', desc: 'Tanggal kegiatan dari Pengumuman resmi otomatis tercatat di Kalender.' },
                    { name: 'Panel agenda terdekat', desc: 'Warga memantau jadwal mendesak langsung dari widget beranda.' }
                ],
                benefits: [
                    'Tidak ada kegiatan yang terlupakan atau bentrok jadwal',
                    'Indikator kategori agenda warna-warni yang jelas',
                    'Pemisahan tab kegiatan lingkup RT dan lingkup RW'
                ]
            },
            galeri: {
                title: 'Galeri Dokumentasi Kegiatan',
                badge: 'Kenangan & Guyub',
                color: 'rose',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
                desc: 'Arsip foto dokumentasi kegiatan bersama warga yang tersimpan rapi per album dengan modal penampil foto (lightbox).',
                steps: [
                    { name: 'Dokumentasi acara lingkungan', desc: 'Pengurus mengunggah dokumentasi kerja bakti, 17 Agustus, atau arisan.' },
                    { name: 'Penyimpanan terorganisir', desc: 'Foto tersusun per album dengan cover otomatis dan penangkal file yatim.' },
                    { name: 'Nikmati kebersamaan', desc: 'Warga melihat foto dengan navigasi Lightbox interaktif dari layar ponsel.' }
                ],
                benefits: [
                    'Kenangan positif lingkungan terjaga rapi dalam arsip digital',
                    'Menumbuhkan rasa bangga dan kebersamaan antarwarga',
                    'Pembersihan penyimpanan fisik otomatis jika album dihapus'
                ]
            }
        };

        const drawerOverlay = document.getElementById('feature-drawer-overlay');
        const featureDrawer = document.getElementById('feature-drawer');
        const drawerCloseBtn = document.getElementById('drawer-close-btn');
        const drawerIconWrap = document.getElementById('drawer-icon-wrap');
        const drawerBadge = document.getElementById('drawer-badge');
        const drawerTitle = document.getElementById('drawer-title');
        const drawerDesc = document.getElementById('drawer-desc');
        const drawerStepsList = document.getElementById('drawer-steps-list');
        const drawerBenefitsList = document.getElementById('drawer-benefits-list');
        const drawerDismissBtns = document.querySelectorAll('.drawer-dismiss-btn');

        function renderFeatureDrawer(key) {
            const data = featureData[key];
            if (!data) return;

            if (drawerTitle) drawerTitle.textContent = data.title;
            if (drawerBadge) {
                drawerBadge.textContent = data.badge;
                drawerBadge.className = 'drawer-badge' + (data.color !== 'amber' ? ' ' + data.color : '');
            }
            if (drawerIconWrap) {
                drawerIconWrap.innerHTML = data.icon;
                drawerIconWrap.className = 'drawer-icon-wrap' + (data.color !== 'amber' ? ' ' + data.color : '');
            }
            if (drawerDesc) drawerDesc.textContent = data.desc;

            if (drawerStepsList) {
                drawerStepsList.innerHTML = data.steps.map((st, idx) => {
                    const num = String(idx + 1).padStart(2, '0');
                    const colorCls = data.color !== 'amber' ? ' ' + data.color : '';
                    return `
                        <div class="drawer-step-item">
                            <span class="drawer-step-badge${colorCls}" aria-hidden="true">${num}</span>
                            <div class="drawer-step-text">
                                <strong class="drawer-step-name">${st.name}</strong>
                                <span class="drawer-step-desc">${st.desc}</span>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            if (drawerBenefitsList) {
                drawerBenefitsList.innerHTML = data.benefits.map(b => `
                    <li class="drawer-benefit-item">
                        <span class="drawer-benefit-check" aria-hidden="true">✓</span>
                        <span>${b}</span>
                    </li>
                `).join('');
            }
        }

        function openDrawer(key) {
            if (!featureDrawer || !drawerOverlay) return;
            renderFeatureDrawer(key);
            drawerOverlay.classList.add('is-active');
            featureDrawer.classList.add('is-active');
            drawerOverlay.setAttribute('aria-hidden', 'false');
            featureDrawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            const body = document.getElementById('drawer-body');
            if (body) body.scrollTop = 0;
        }

        function closeDrawer() {
            if (!featureDrawer || !drawerOverlay) return;
            drawerOverlay.classList.remove('is-active');
            featureDrawer.classList.remove('is-active');
            drawerOverlay.setAttribute('aria-hidden', 'true');
            featureDrawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-feature-trigger]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openDrawer(btn.getAttribute('data-feature-trigger'));
            });
        });

        document.querySelectorAll('.feature-card[data-feature-card]').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('[data-feature-trigger]')) return;
                openDrawer(card.getAttribute('data-feature-card'));
            });
        });

        if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', closeDrawer);
        if (drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);
        drawerDismissBtns.forEach(btn => btn.addEventListener('click', closeDrawer));

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && featureDrawer && featureDrawer.classList.contains('is-active')) {
                closeDrawer();
            }
        });
    })();
</script>
</body>
</html>

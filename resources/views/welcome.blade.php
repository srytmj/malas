<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manga Library — MALAS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: #fffbeb;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* ── Background decorative blobs ── */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            pointer-events: none;
            z-index: 0;
        }
        .blob-1 { width: 420px; height: 420px; background: #fbcfe8; top: -100px; left: -100px; }
        .blob-2 { width: 350px; height: 350px; background: #bfdbfe; bottom: -80px; right: -80px; }
        .blob-3 { width: 250px; height: 250px; background: #a7f3d0; bottom: 80px; left: 60px; opacity: 0.25; }
        .blob-4 { width: 200px; height: 200px; background: #e9d5ff; top: 100px; right: 80px; opacity: 0.25; }

        /* ── Floating dots ── */
        .dots {
            position: fixed;
            pointer-events: none;
            z-index: 0;
        }
        .dot {
            position: absolute;
            border-radius: 50%;
            opacity: 0.5;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
        }

        /* ── Card ── */
        .card {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 32px;
            padding: 56px 64px;
            max-width: 560px;
            width: calc(100% - 32px);
            box-shadow:
                0 4px 24px rgba(203, 213, 225, 0.35),
                0 1px 4px rgba(203, 213, 225, 0.2);
            text-align: center;
        }

        /* ── Logo ── */
        .logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 32px;
        }
        .logo-wrap img {
            width: 100%;
            max-width: 340px;
            height: auto;
        }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 auto 28px;
            max-width: 280px;
        }
        .divider-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, transparent, #fed7aa, transparent);
        }
        .divider-icon {
            font-size: 16px;
            opacity: 0.6;
        }

        /* ── Description ── */
        .desc {
            font-size: 14px;
            line-height: 1.7;
            color: #a8a29e;
            margin-bottom: 36px;
        }
        .desc strong {
            color: #78716c;
            font-weight: 600;
        }

        /* ── Login button ── */
        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 36px;
            border-radius: 50px;
            background: linear-gradient(135deg, #fbcfe8 0%, #fed7aa 100%);
            color: #78716c;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 0.5px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
            box-shadow: 0 4px 16px rgba(251, 207, 232, 0.5);
            border: none;
            cursor: pointer;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(251, 207, 232, 0.6);
            opacity: 0.9;
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .btn-icon {
            font-size: 18px;
        }

        /* ── Footer ── */
        .footer {
            position: relative;
            z-index: 1;
            margin-top: 28px;
            font-size: 12px;
            color: #d6d3d1;
            letter-spacing: 0.5px;
        }

        /* ── Floating flowers (decorative) ── */
        .flower {
            position: fixed;
            pointer-events: none;
            z-index: 0;
            opacity: 0;
            animation: fadeFloat 8s ease-in-out infinite;
        }
        @keyframes fadeFloat {
            0%, 100% { opacity: 0; transform: translateY(0) rotate(0deg); }
            20%, 80% { opacity: 0.4; }
            50% { transform: translateY(-20px) rotate(15deg); }
        }

        @media (max-width: 480px) {
            .card { padding: 40px 28px; }
        }
    </style>
</head>
<body>

    {{-- Background blobs --}}
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>

    {{-- Floating dots --}}
    <div class="dots">
        <div class="dot" style="width:10px;height:10px;background:#fbcfe8;top:18%;left:12%;animation-delay:0s;animation-duration:5s;"></div>
        <div class="dot" style="width:6px;height:6px;background:#fde047;top:30%;left:8%;animation-delay:1.5s;animation-duration:7s;"></div>
        <div class="dot" style="width:8px;height:8px;background:#bfdbfe;top:65%;left:15%;animation-delay:0.8s;animation-duration:6s;"></div>
        <div class="dot" style="width:10px;height:10px;background:#a7f3d0;top:20%;right:12%;animation-delay:2s;animation-duration:5.5s;"></div>
        <div class="dot" style="width:6px;height:6px;background:#e9d5ff;top:55%;right:8%;animation-delay:0.3s;animation-duration:8s;"></div>
        <div class="dot" style="width:8px;height:8px;background:#fed7aa;top:75%;right:18%;animation-delay:1.2s;animation-duration:6.5s;"></div>
        <div class="dot" style="width:5px;height:5px;background:#fbcfe8;top:82%;left:30%;animation-delay:3s;animation-duration:7s;"></div>
        <div class="dot" style="width:7px;height:7px;background:#fde047;top:10%;right:35%;animation-delay:0.5s;animation-duration:5s;"></div>
    </div>

    {{-- Main card --}}
    <main class="card">
        <div class="logo-wrap">
            <img src="{{ asset('images/logo.svg') }}" alt="Manga Library" draggable="false">
        </div>

        <div class="divider">
            <div class="divider-line"></div>
            <span class="divider-icon">✦</span>
            <div class="divider-line"></div>
        </div>

        <p class="desc">
            Kelola koleksi manga fisikmu — lacak volume, pinjaman, dan kondisi buku — semuanya dalam satu tempat yang rapi dan mudah diakses.
        </p>

        <a href="{{ url('/admin') }}" class="btn-login">
            <span class="btn-icon">✦</span>
            Masuk ke Panel
        </a>
    </main>

    <p class="footer">Private access only &nbsp;·&nbsp; MALAS &copy; {{ date('Y') }}</p>

</body>
</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SRC Logo Voting | Santa Rita College of Pampanga</title>
    <link rel="icon" href="logo/srclogo.png" type="image/x-icon">
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        /* Modern Dark Theme with Light Blue Colors */
        :root {
            --primary: #3B82F6;
            --primary-glow: rgba(59, 130, 246, 0.4);
            --secondary: #06B6D4;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --gold: #D4AF37;
            --bg-body: #0F172A;
            --bg-card: rgba(30, 41, 59, 0.7);
            --bg-sidebar: #1E293B;
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --border: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.05);
            --radius-lg: 24px;
            --radius-md: 16px;
            --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html {
            scroll-behavior: smooth;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        /* Animated background orbs */
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.15;
            z-index: -1;
            animation: float 20s ease-in-out infinite;
        }

        body::before {
            width: 500px;
            height: 500px;
            background: var(--primary);
            top: -250px;
            left: -250px;
        }

        body::after {
            width: 400px;
            height: 400px;
            background: var(--secondary);
            bottom: -200px;
            right: -200px;
            animation-delay: 10s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(30px, -30px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        /* Sticky Navigation Bar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 18px 0;
            z-index: 1000;
            transition: var(--transition);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }

        .navbar.scrolled {
            background: rgba(30, 41, 59, 0.95);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
            padding: 14px 0;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }

        .nav-logo {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            transition: var(--transition);
            box-shadow: 0 0 20px var(--primary-glow);
            filter: drop-shadow(0 0 10px var(--primary-glow));
        }

        .nav-brand:hover .nav-logo {
            transform: scale(1.05) rotate(5deg);
            border-color: var(--gold);
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.6);
        }

        .nav-text {
            display: flex;
            flex-direction: column;
        }

        .nav-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
            line-height: 1.2;
            background: linear-gradient(135deg, var(--text-main), var(--text-muted));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 0;
            font-weight: 500;
        }

        .nav-auth {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .nav-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .nav-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-btn:hover::before {
            left: 100%;
        }

        .nav-btn.login {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .nav-btn.login:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .nav-btn.register {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: 2px solid transparent;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .nav-btn.register:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px var(--primary-glow);
        }

        body {
            line-height: 1.6;
            color: var(--text-main);
            background-color: var(--bg-body);
            margin: 0;
            padding-top: 95px;
            position: relative;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            background-image:
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.1) 0px, transparent 50%);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
        }

        /* Header with dark gradient overlay */
        header {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.9) 0%, rgba(6, 182, 212, 0.85) 100%),
                url('logo/srcfront.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 5rem 0 4rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.3);
            z-index: 1;
        }

        header::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--warning), var(--primary));
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
            z-index: 3;
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .header-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
            text-align: center;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.025em;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }

        .subtitle {
            font-size: 1.25rem;
            font-weight: 500;
            opacity: 0.95;
            margin-bottom: 1.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 0 1px 10px rgba(0, 0, 0, 0.2);
        }

        .countdown {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 1rem;
            gap: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Hero section with glassmorphic card */
        .hero {
            text-align: center;
            margin: 0 auto 4rem;
            max-width: 800px;
            position: relative;
        }

        .hero-content {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border);
            border-left: 4px solid var(--gold);
        }

        .hero-content p {
            font-size: 1.125rem;
            line-height: 1.7;
            color: var(--text-main);
            margin-bottom: 1.5rem;
        }

        .main-vote-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 700;
            font-size: 1.125rem;
            transition: var(--transition);
            box-shadow: 0 4px 15px var(--primary-glow);
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }

        .main-vote-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .main-vote-btn:hover::before {
            left: 100%;
        }

        .main-vote-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--primary-glow);
        }

        .main-vote-btn i {
            margin-right: 0.75rem;
        }

        /* Section styling */
        .section {
            margin-bottom: 4rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.25rem;
            color: var(--text-main);
            margin-bottom: 2.5rem;
            text-align: center;
            position: relative;
            display: inline-block;
            font-weight: 700;
        }

        .section-title::after {
            content: "";
            position: absolute;
            bottom: -0.75rem;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--primary));
            border-radius: 2px;
        }

        /* Logo grid with glassmorphic cards */
        .logo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .logo-option {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .logo-option:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            border-color: var(--primary);
        }

        .logo-option::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .logo-img-container {
            width: 100%;
            height: 280px;
            background: rgba(79, 70, 229, 0.05);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            border-bottom: 1px solid var(--border);
        }

        .logo-img {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
            transition: var(--transition);
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }

        .logo-option:hover .logo-img {
            transform: scale(1.08);
        }

        .logo-info {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .logo-title {
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
        }

        .logo-description {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            flex-grow: 1;
            line-height: 1.6;
        }

        .vote-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px var(--primary-glow);
            align-self: flex-start;
        }

        .vote-btn:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            box-shadow: 0 6px 20px var(--primary-glow);
            transform: translateY(-2px);
        }

        .vote-btn i {
            margin-right: 0.5rem;
        }

        /* Rules section with glassmorphism */
        .rules {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: var(--radius-lg);
            margin-bottom: 4rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border);
            border-top: 4px solid var(--gold);
        }

        .rules h2 {
            color: var(--text-main);
            margin-bottom: 2rem;
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            position: relative;
            display: inline-block;
        }

        .rules h2::after {
            content: "";
            position: absolute;
            bottom: -0.75rem;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gold);
            border-radius: 2px;
        }

        .rules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .rule-card {
            background: rgba(59, 130, 246, 0.1);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            border-left: 3px solid var(--primary);
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .rule-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            background: rgba(59, 130, 246, 0.15);
        }

        .rule-card h3 {
            color: var(--text-main);
            margin-bottom: 1rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .rule-card ul {
            list-style-type: none;
        }

        .rule-card li {
            margin-bottom: 0.75rem;
            position: relative;
            padding-left: 1.5rem;
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--text-muted);
        }

        .rule-card li::before {
            content: "•";
            color: var(--gold);
            font-weight: bold;
            position: absolute;
            left: 0.5rem;
            font-size: 1.2rem;
        }

        /* Stats section */
        .stats {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            min-width: 180px;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            border-top: 3px solid var(--primary);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Footer with dark theme */
        footer {
            background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
            color: var(--text-main);
            padding: 4rem 0;
            position: relative;
            margin-top: 4rem;
            border-top: 1px solid var(--border);
        }

        footer::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--warning), var(--primary));
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        /* Footer Brand Section */
        .footer-brand {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .footer-logo {
            max-width: 160px;
            margin-bottom: 1.5rem;
            opacity: 0.95;
            filter: drop-shadow(0 2px 8px rgba(79, 70, 229, 0.3));
        }

        .footer-about {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .footer-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 1rem;
        }

        .footer-admin-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 25px;
            transition: var(--transition);
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .footer-admin-btn:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px var(--primary-glow);
        }

        .footer-student-register {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-student-register:hover {
            color: var(--text-main);
            text-decoration: underline;
        }

        /* Mission Vision Philosophy Grid */
        .footer-mvp-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-links-container {
            display: flex;
            flex-direction: column;
            background: rgba(59, 130, 246, 0.05);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            border-left: 3px solid var(--gold);
            border: 1px solid var(--border);
            transition: var(--transition);
            height: 100%;
        }

        .footer-links-container:hover {
            background: rgba(59, 130, 246, 0.1);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .footer-links-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: var(--gold);
            position: relative;
            display: inline-block;
            font-weight: 700;
        }

        .footer-links-title::after {
            content: "";
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 40px;
            height: 2px;
            background: var(--gold);
        }

        .footer-content-text {
            font-size: 0.95rem;
            line-height: 1.7;
            color: var(--text-muted);
            text-align: justify;
            flex-grow: 1;
        }

        .copyright {
            text-align: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Watermark logo effect */
        .watermark-logo {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            opacity: 0.03;
            z-index: -1;
            pointer-events: none;
            width: 600px;
            height: 600px;
            background-image: url('logo/srclogo.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            25% {
                transform: translate(-15px, -10px) rotate(1deg);
            }

            50% {
                transform: translate(10px, 5px) rotate(-1deg);
            }

            75% {
                transform: translate(-5px, 10px) rotate(1deg);
            }
        }

        /* Carousel Styling */
        .carousel-container {
            position: relative;
            max-width: 900px;
            margin: 0 auto 3rem;
            overflow: hidden;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            background: var(--bg-card);
            backdrop-filter: blur(20px);
        }

        .carousel-slide {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }

        .carousel-slide img {
            width: 100%;
            flex-shrink: 0;
            object-fit: contain;
            background: rgba(59, 130, 246, 0.05);
            padding: 20px;
        }

        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(59, 130, 246, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            color: white;
            font-size: 2rem;
            padding: 8px 14px;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .carousel-btn:hover {
            background: var(--primary);
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 20px var(--primary-glow);
        }

        .carousel-btn.prev {
            left: 1rem;
        }

        .carousel-btn.next {
            right: 1rem;
        }

        transition: var(--srcp-transition);
        }

        .carousel-btn:hover {
            background: var(--srcp-gold);
        }

        .carousel-btn.prev {
            left: 10px;
        }

        .carousel-btn.next {
            right: 10px;
        }

        /* Mobile Responsiveness */
        @media (max-width: 1024px) {
            .watermark-logo {
                width: 500px;
                height: 500px;
            }

            .footer-mvp-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 12px 0;
            }

            .navbar.scrolled {
                padding: 10px 0;
            }

            .nav-container {
                padding: 0 1rem;
            }

            .nav-logo {
                width: 45px;
                height: 45px;
            }

            .nav-title {
                font-size: 1.2rem;
            }

            .nav-subtitle {
                display: none;
            }

            .nav-auth {
                gap: 8px;
            }

            .nav-btn {
                padding: 8px 16px;
                font-size: 0.85rem;
            }

            body {
                padding-top: 75px;
            }

            h1 {
                font-size: 2.25rem;
            }

            .subtitle {
                font-size: 1.1rem;
            }

            .logo-grid {
                grid-template-columns: 1fr;
            }

            .rules {
                padding: 2rem;
            }

            .watermark-logo {
                width: 400px;
                height: 400px;
            }

            .footer-content {
                padding: 0 1rem;
            }

            .footer-mvp-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .footer-links-container {
                padding: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .nav-container {
                padding: 0 0.75rem;
            }

            .nav-logo {
                width: 40px;
                height: 40px;
            }

            .nav-title {
                font-size: 1.1rem;
            }

            .nav-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }

            .nav-btn i {
                display: none;
            }

            body {
                padding-top: 65px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .section-title {
                font-size: 1.75rem;
            }

            .hero-content {
                padding: 1.5rem;
            }

            .watermark-logo {
                width: 300px;
                height: 300px;
                bottom: 1rem;
                right: 1rem;
            }

            .footer-content {
                padding: 0 0.75rem;
            }

            .footer-links-container {
                padding: 1rem;
            }

            .footer-links-title {
                font-size: 1.2rem;
            }
        }

        /* Animation classes */
        .fade-in {
            opacity: 0;
            transform: translateY(15px) translateZ(0);
            transition: opacity 0.8s cubic-bezier(0.2, 0.8, 0.2, 1), 
                        transform 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
            will-change: opacity, transform;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0) translateZ(0);
        }
    </style>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <!-- Sticky Navigation Bar -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="#" class="nav-brand">
                <img src="logo/srclogo.png" alt="SRC Logo" class="nav-logo">
                <div class="nav-text">
                    <div class="nav-title">Voting System</div>
                    <div class="nav-subtitle">Santa Rita College of Pampanga, Inc</div>
                </div>
            </a>
            <div>
                <button onclick="openLoginModal()" class="nav-btn login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </div>
        </div>
    </nav>

    <div class="container">
        <header>
            <div class="header-content">
                <h1>Santa Rita College of Pampanga</h1>
                <div class="subtitle"></div>
                <div class="countdown">
                    <i class=""></i> Santa Rita College Voting System
                </div>
            </div>
        </header>

        <?php
        // Fetch latest election winners
        require_once 'includes/get_winners.php';
        $winnersData = getLatestElectionWinners($pdo);

        if ($winnersData && !empty($winnersData['winners'])):
            $election = $winnersData['election'];
            $winners = $winnersData['winners'];
            $totalVoters = $winnersData['total_voters'];
            ?>

            <!-- Election Winners Announcement -->
            <section class="winners-announcement" style="margin-top: 2rem; margin-bottom: 4rem;">
                <div class="winners-header">
                    <div class="winners-badge">
                        <i class="fas fa-trophy"></i>
                        <span>Official Results</span>
                    </div>
                    <h2 class="winners-title">
                        <?php echo htmlspecialchars($election['title']); ?> - Winners
                    </h2>
                    <p class="winners-subtitle">
                        <i class="fas fa-calendar-check"></i>
                        Concluded on
                        <?php echo date('F j, Y', strtotime($election['end_datetime'])); ?>
                        <span class="voter-turnout">
                            <i class="fas fa-users"></i>
                            <?php echo number_format($totalVoters); ?> voters participated
                        </span>
                    </p>
                </div>

                <div class="winners-grid">
                    <?php foreach ($winners as $index => $winner): ?>
                        <div class="winner-card fade-in" style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                            <?php if ($winner['position'] === 'President'): ?>
                                <div class="winner-crown">
                                    <i class="fas fa-crown"></i>
                                </div>
                            <?php endif; ?>

                            <div class="winner-photo-container">
                                <?php
                                $photo_path = $winner['photo_path'] ?: 'pic/default-avatar.png';
                                // Remove leading '../' if present since index.php is in the root
                                if (strpos($photo_path, '../') === 0) {
                                    $photo_path = substr($photo_path, 3);
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($photo_path); ?>"
                                    alt="<?php echo htmlspecialchars($winner['first_name'] . ' ' . $winner['last_name']); ?>"
                                    class="winner-photo" onerror="this.src='pic/default-avatar.png'">
                                <div class="winner-position-badge">
                                    <?php echo htmlspecialchars($winner['position']); ?>
                                </div>
                            </div>

                            <div class="winner-info">
                                <h3 class="winner-name">
                                    <?php echo htmlspecialchars($winner['first_name'] . ' ' . $winner['last_name']); ?>
                                </h3>
                                <p class="winner-details">
                                    <?php echo htmlspecialchars($winner['year'] . ' - ' . $winner['section']); ?>
                                </p>
                                <div class="winner-votes">
                                    <i class="fas fa-vote-yea"></i>
                                    <span class="vote-count">
                                        <?php echo number_format($winner['votes']); ?>
                                    </span>
                                    <span class="vote-label">votes</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="winners-footer">
                    <p><i class="fas fa-info-circle"></i> Congratulations to all our newly elected leaders!</p>
                </div>
            </section>

            <style>
                /* Winners Announcement Styles */
                .winners-announcement {
                    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(6, 182, 212, 0.05) 100%),
                        var(--bg-card);
                    backdrop-filter: blur(20px);
                    -webkit-backdrop-filter: blur(20px);
                    border-radius: var(--radius-lg);
                    padding: 3rem 2rem;
                    box-shadow: var(--shadow);
                    border: 1px solid var(--border);
                    border-top: 5px solid var(--gold);
                    position: relative;
                    overflow: hidden;
                }

                .winners-announcement::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background:
                        radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 90% 80%, rgba(6, 182, 212, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 50% 50%, rgba(212, 175, 55, 0.03) 0%, transparent 70%);
                    pointer-events: none;
                    z-index: 0;
                }

                .winners-announcement>* {
                    position: relative;
                    z-index: 1;
                }

                .winners-header {

                    text-align: center;
                    margin-bottom: 3rem;
                    position: relative;
                    z-index: 1;
                }

                .winners-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.5rem;
                    background: linear-gradient(135deg, var(--gold), var(--warning));
                    color: var(--bg-body);
                    padding: 0.5rem 1.5rem;
                    border-radius: 50px;
                    font-weight: 700;
                    font-size: 0.875rem;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    margin-bottom: 1rem;
                    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.4);
                }

                .winners-badge i {
                    font-size: 1.1rem;
                }

                .winners-title {
                    font-family: 'Playfair Display', serif;
                    font-size: 2.5rem;
                    color: var(--text-main);
                    margin-bottom: 1rem;
                    font-weight: 800;
                }

                .winners-subtitle {
                    color: var(--text-muted);
                    font-size: 1rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 1.5rem;
                    flex-wrap: wrap;
                }

                .voter-turnout {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.5rem;
                    background: rgba(59, 130, 246, 0.2);
                    padding: 0.25rem 1rem;
                    border-radius: 20px;
                    font-weight: 600;
                    color: var(--primary);
                    border: 1px solid rgba(59, 130, 246, 0.3);
                }

                .winners-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 2rem;
                    margin-bottom: 2rem;
                    position: relative;
                    z-index: 1;
                }

                .winner-card {
                    background: var(--bg-card);
                    backdrop-filter: blur(20px);
                    -webkit-backdrop-filter: blur(20px);
                    border-radius: var(--radius-lg);
                    padding: 2rem 1.5rem;
                    text-align: center;
                    box-shadow: var(--shadow);
                    border: 1px solid var(--border);
                    transition: var(--transition);
                    position: relative;
                    overflow: hidden;
                }

                .winner-card::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, var(--primary), var(--secondary));
                }

                .winner-card:hover {
                    transform: translateY(-10px);
                    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
                    border-color: var(--primary);
                }

                .winner-crown {
                    position: absolute;
                    top: -10px;
                    right: -10px;
                    background: linear-gradient(135deg, var(--gold), var(--warning));
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--bg-body);
                    font-size: 1.5rem;
                    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.5);
                    animation: crownPulse 2s ease-in-out infinite;
                    border: 2px solid var(--bg-body);
                }

                @keyframes crownPulse {

                    0%,
                    100% {
                        transform: scale(1);
                    }

                    50% {
                        transform: scale(1.1);
                    }
                }

                .winner-photo-container {
                    position: relative;
                    width: 140px;
                    height: 140px;
                    margin: 0 auto 1.5rem;
                }

                .winner-photo {
                    width: 100%;
                    height: 100%;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 4px solid var(--primary);
                    box-shadow: 0 8px 20px var(--primary-glow);
                    transition: var(--transition);
                }

                .winner-card:hover .winner-photo {
                    transform: scale(1.05);
                    border-color: var(--gold);
                    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.6);
                }

                .winner-position-badge {
                    position: absolute;
                    bottom: -5px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: linear-gradient(135deg, var(--primary), var(--secondary));
                    color: white;
                    padding: 0.35rem 1rem;
                    border-radius: 20px;
                    font-size: 0.75rem;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    box-shadow: 0 4px 12px var(--primary-glow);
                    white-space: nowrap;
                }

                .winner-name {
                    font-size: 1.25rem;
                    font-weight: 700;
                    color: var(--text-main);
                    margin-bottom: 0.5rem;
                }

                .winner-votes {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    color: var(--text-muted);
                    font-size: 0.95rem;
                    margin-bottom: 1rem;
                }

                .winner-votes i {
                    color: var(--primary);
                }

                .winner-stats {
                    display: flex;
                    justify-content: space-around;
                    padding-top: 1rem;
                    border-top: 1px solid var(--border);
                }

                .winner-stat {
                    text-align: center;
                }

                .winner-stat-value {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: var(--primary);
                    display: block;
                }

                .winner-stat-label {
                    font-size: 0.75rem;
                    color: var(--text-muted);
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .winner-details {
                    color: var(--text-muted);
                    font-size: 0.9rem;
                    margin-bottom: 1rem;
                }

                .vote-count {
                    font-size: 1.5rem;
                    font-weight: 800;
                }

                .vote-label {
                    font-size: 0.85rem;
                    opacity: 0.8;
                }

                .winners-footer {
                    text-align: center;
                    padding-top: 2rem;
                    border-top: 2px dashed var(--border);
                    color: var(--text-main);
                    font-weight: 600;
                    position: relative;
                    z-index: 1;
                }

                .winners-footer i {
                    margin-right: 0.5rem;
                    color: var(--gold);
                }

                /* Responsive adjustments */
                @media (max-width: 768px) {
                    .winners-title {
                        font-size: 1.75rem;
                    }

                    .winners-subtitle {
                        flex-direction: column;
                        gap: 0.5rem;
                    }

                    .winners-grid {
                        grid-template-columns: 1fr;
                        gap: 1.5rem;
                    }
                }
            </style>
            <script>
                // Celebration effect for homepage winners
                window.addEventListener('load', () => {
                    const duration = 5 * 1000;
                    const end = Date.now() + duration;

                    (function frame() {
                        confetti({
                            particleCount: 2,
                            angle: 60,
                            spread: 55,
                            origin: { x: 0, y: 0.8 },
                            colors: ['#4F46E5', '#D4AF37', '#7C3AED']
                        });
                        confetti({
                            particleCount: 2,
                            angle: 120,
                            spread: 55,
                            origin: { x: 1, y: 0.8 },
                            colors: ['#005BAA', '#D4AF37']
                        });

                        if (Date.now() < end) {
                            requestAnimationFrame(frame);
                        }
                    }());
                });
            </script>
        <?php endif; ?>

        <section class="hero">
            <div class="hero-content fade-in">
                <p>Help shape our institutional identity! Vote for the leaders that best represents SRC's values,
                    heritage, and vision for the future. The winning design will become our official emblem for the next
                    decade.</p>
                <a href="#vote" class="main-vote-btn">
                    <i class="fas fa-vote-yea"></i> View All Department Logos
                </a>
            </div>
        </section>

        <!-- Carousel Section -->
        <section class="section fade-in">
            <h2 class="section-title"></h2>
            <div class="carousel-container">
                <div class="carousel-slide">
                    <img src="logo/srcfront.jpg" alt="SRC Highlight 1" loading="lazy">
                    <img src="logo/srcfrontlogo.jpg" alt="SRC Highlight 2" loading="lazy">
                    <img src="logo/dome.jpg" alt="SRC Highlight 3" loading="lazy">
                    <img src="logo/srccdome.jpg" alt="SRC Highlight 4" loading="lazy">
                </div>
                <button class="carousel-btn prev">❮</button>
                <button class="carousel-btn next">❯</button>
            </div>
        </section>


        <section class="section" id="vote">
            <h2 class="section-title fade-in">Department Logo Options</h2>

            <div class="logo-grid">
                <!-- Logo Option 1 -->
                <div class="logo-option fade-in">
                    <div class="logo-img-container">
                        <img src="logo/elem-removebg-preview.png" alt="Elementary Department Logo" class="logo-img">
                    </div>
                    <div class="logo-info">
                        <div class="logo-title">Elementary Department</div>
                        <div style="display: flex; gap: 10px; align-items: center;">


                        </div>
                    </div>
                </div>

                <!-- Logo Option 2 -->
                <div class="logo-option fade-in">
                    <div class="logo-img-container">
                        <img src="logo/integrated-removebg-preview.png" alt="Integrated High School Logo"
                            class="logo-img">
                    </div>
                    <div class="logo-info">
                        <div class="logo-title">Integrated High School</div>
                        <div style="display: flex; gap: 10px; align-items: center;">


                        </div>
                    </div>
                </div>

                <!-- Logo Option 3 -->
                <div class="logo-option fade-in">
                    <div class="logo-img-container">
                        <img src="logo/ccs-logo.png" alt="Computer Studies Logo" class="logo-img">
                    </div>
                    <div class="logo-info">
                        <div class="logo-title">College of Computer Studies</div>
                        <div style="display: flex; gap: 10px; align-items: center;">


                        </div>
                    </div>
                </div>

                <!-- Logo Option 4 -->
                <div class="logo-option fade-in">
                    <div class="logo-img-container">
                        <img src="logo/cbs-removebg-preview.png" alt="Business Studies Logo" class="logo-img">
                    </div>
                    <div class="logo-info">
                        <div class="logo-title">College of Business Studies</div>
                        <div style="display: flex; gap: 10px; align-items: center;">


                        </div>
                    </div>
                </div>

                <!-- Logo Option 5 -->
                <div class="logo-option fade-in">
                    <div class="logo-img-container">
                        <img src="logo/coe-removebg-preview.png" alt="Education Logo" class="logo-img">
                    </div>
                    <div class="logo-info">
                        <div class="logo-title">College of Education</div>
                        <div style="display: flex; gap: 10px; align-items: center;">


                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="rules fade-in">
                <h2>Voting Guidelines & Process</h2>
                <div class="rules-grid">
                    <div class="rule-card">
                        <h3><i class="fas fa-user-graduate"></i> Eligibility</h3>
                        <ul>
                            <li>Current enrolled students</li>
                            <li>Faculty and staff members</li>
                            <li></li>
                            <li>Board of Trustees members</li>
                        </ul>
                    </div>
                    <div class="rule-card">
                        <h3><i class="fas fa-clipboard-check"></i> Voting Process</h3>
                        <ul>
                            <li>One vote per department</li>
                            <li>Voting open for set hours</li>
                            <li>Real-time results tracking</li>
                            <li>Secure authentication required</li>
                        </ul>
                    </div>
                    <div class="rule-card">
                        <h3><i class="fas fa-award"></i> Selection Criteria</h3>
                        <ul>
                            <li>Most votes wins</li>
                            <li>50%+ majority required</li>
                            <li>Runoff if no majority</li>
                            <li>By Department Admin final approval</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <div class="watermark-logo"></div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <img src="logo/srclogo.png" alt="SRC Logo" class="footer-logo">
                <p class="footer-about">
                    Santa Rita College of Pampanga
                </p>
                <div class="footer-actions">
                    
                </div>
            </div>

            <!-- Philosophy, Mission, Vision Grid - Side by side on PC -->
            <div class="footer-mvp-grid">
                <div class="footer-links-container">
                    <h3 class="footer-links-title">Philosophy</h3>
                    <p class="footer-content-text">
                        We believe that education is transforming God-centered individuals in a nurturing learning
                        environment.
                    </p>
                </div>

                <div class="footer-links-container">
                    <h3 class="footer-links-title">Mission</h3>
                    <p class="footer-content-text">
                        We are dedicated to develop and nurture individuals who are stewards of God's creation and of
                        Christian faith, responsible leaders and citizens with passion to serve God and Humanity.
                        Committed to academic excellence.
                    </p>
                </div>

                <div class="footer-links-container">
                    <h3 class="footer-links-title">Vision</h3>
                    <p class="footer-content-text">
                        A center of exellence dedicated in the transformation of individuals for the service of God and
                        Humanity.
                    </p>
                </div>
            </div>

            <div class="copyright">
                © 2025 Santa Rita College of Pampanga. All Rights Reserved.

                Office of the President • Logo Selection Committee
            </div>

        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function () {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Improved intersection observer for animations
        document.addEventListener('DOMContentLoaded', function () {
            const animatedElements = document.querySelectorAll('.fade-in');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            animatedElements.forEach(el => {
                observer.observe(el);
            });

            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
    <script>
        const slide = document.querySelector('.carousel-slide');
        const images = document.querySelectorAll('.carousel-slide img');
        const prevBtn = document.querySelector('.carousel-btn.prev');
        const nextBtn = document.querySelector('.carousel-btn.next');

        let counter = 0;
        let isTransitioning = false;

        function showSlide(index) {
            if (isTransitioning) return;
            isTransitioning = true;
            
            if (index >= images.length) counter = 0;
            else if (index < 0) counter = images.length - 1;
            else counter = index;
            
            slide.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            slide.style.transform = `translateX(${-counter * 100}%)`;
            
            setTimeout(() => {
                isTransitioning = false;
            }, 600);
        }

        nextBtn.addEventListener('click', () => {
            showSlide(counter + 1);
        });

        prevBtn.addEventListener('click', () => {
            showSlide(counter - 1);
        });

        // Touch support for carousel
        let touchStartX = 0;
        let touchEndX = 0;

        slide.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        slide.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            if (touchStartX - touchEndX > 50) showSlide(counter + 1);
            if (touchEndX - touchStartX > 50) showSlide(counter - 1);
        }

        // Auto-slide every 5 seconds
        let autoSlide = setInterval(() => {
            showSlide(counter + 1);
        }, 5000);

        // Reset timer on manual interaction
        const resetTimer = () => {
            clearInterval(autoSlide);
            autoSlide = setInterval(() => {
                showSlide(counter + 1);
            }, 5000);
        };

        [prevBtn, nextBtn].forEach(btn => btn.addEventListener('click', resetTimer));
        slide.addEventListener('touchend', resetTimer);
    </script>

    <!-- Login Selection Modal -->
    <div id="loginModal" class="login-modal">
        <div class="login-modal-overlay" onclick="closeLoginModal()"></div>
        <div class="login-modal-content">
            <button class="modal-close" onclick="closeLoginModal()">
                <i class="fas fa-times"></i>
            </button>

            <div class="modal-header">
                <div class="modal-icon-header">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Welcome Back</h3>
                <p>Please select your login portal</p>
            </div>

            <div class="login-options-grid">
                <a href="login.php" class="login-option-card">
                    <div class="option-icon student">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="option-text">
                        <h4>Login as Student</h4>
                        <p>Access your student voting portal</p>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </a>

                <a href="admin/login.php" class="login-option-card">
                    <div class="option-icon admin">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="option-text">
                        <h4>Login as Admin</h4>
                        <p>Department & System Management</p>
                    </div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                </a>
            </div>

            <div class="modal-footer-note">
                <p>Protected by Secure Authentication System</p>
            </div>
        </div>
    </div>

    <style>
        /* Login Modal Styling */
        .login-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .login-modal.active {
            display: flex;
        }

        .login-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease;
        }

        .login-modal-content {
            position: relative;
            width: 90%;
            max-width: 500px;
            background: #1E293B;
            border-radius: 28px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            z-index: 2001;
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .login-modal.active .login-modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: #94A3B8;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
            transform: rotate(90deg);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .modal-icon-header {
            width: 64px;
            height: 64px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #3B82F6;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.3);
        }

        .modal-header h3 {
            font-size: 1.8rem;
            margin-bottom: 8px;
            color: #F8FAFC;
        }

        .modal-header p {
            color: #94A3B8;
            font-size: 0.95rem;
        }

        .login-options-grid {
            display: grid;
            gap: 16px;
        }

        .login-option-card {
            display: flex;
            align-items: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.2s;
            gap: 20px;
        }

        .login-option-card:hover {
            background: rgba(59, 130, 246, 0.08);
            border-color: rgba(59, 130, 246, 0.3);
            transform: translateX(5px);
        }

        .option-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .option-icon.student {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }

        .option-icon.admin {
            background: rgba(99, 102, 241, 0.1);
            color: #6366F1;
        }

        .option-text {
            flex-grow: 1;
        }

        .option-text h4 {
            color: #F8FAFC;
            font-size: 1.1rem;
            margin-bottom: 4px;
        }

        .option-text p {
            color: #94A3B8;
            font-size: 0.85rem;
        }

        .arrow-icon {
            color: #475569;
            font-size: 0.9rem;
            transition: transform 0.2s;
        }

        .login-option-card:hover .arrow-icon {
            transform: translateX(3px);
            color: #3B82F6;
        }

        .modal-footer-note {
            text-align: center;
            margin-top: 30px;
            color: #475569;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>

    <script>
        function openLoginModal() {
            const modal = document.getElementById('loginModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLoginModal() {
            const modal = document.getElementById('loginModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeLoginModal();
        });
    </script>
</body>

</html>
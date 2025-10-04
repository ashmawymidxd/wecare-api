<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Wecare API</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, red, orange, yellow);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Starry background */
        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .star {
            position: absolute;
            background-color: white;
            border-radius: 50%;
            animation: twinkle 5s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.2; }
            50% { opacity: 1; }
        }

        /* Shooting stars */
        .shooting-star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 0 10px 2px white;
            opacity: 0;
        }

        @keyframes shoot {
            0% {
                transform: translateX(0) translateY(0);
                opacity: 1;
            }
            100% {
                transform: translateX(100vw) translateY(100vh);
                opacity: 0;
            }
        }

        /* Card with magical effects */
        .card {
            margin: 10px;
            background: rgba(253, 253, 253);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 1.25rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.5s ease;
            z-index: 1;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            background: rgb(255, 255, 255,0.726);
        }

        .card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(0deg);
            transition: transform 0.5s ease;
            z-index: -1;
        }

        .card:hover::before {
            transform: rotate(180deg);
        }

        h1 {
            margin: 0;
            font-size: 2.5rem;
            color: red;
            text-shadow: 0 0 10px rgba(255, 204, 0, 0.5);
            background: linear-gradient(45deg, #ffcc00, #ff9900);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: glow 2s infinite alternate;
            margin-bottom: 1rem;
        }

        @keyframes glow {
            from {
                text-shadow: 0 0 5px rgba(255, 204, 0, 0.5);
            }
            to {
                text-shadow: 0 0 20px rgba(255, 204, 0, 0.8), 0 0 30px rgba(255, 204, 0, 0.6);
            }
        }

        p {
            margin: 1rem 0;
            font-size: 1.1rem;
            color: black;
            line-height: 1.6;
        }

        .badge {
            display: inline-block;
            margin-top: 1rem;
            background: linear-gradient(45deg, #ffcc00, #ff9900);
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 9999px;
            font-size: 1rem;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 204, 0, 0.3);
        }

        .badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .badge:hover::before {
            left: 100%;
        }

        .badge:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(255, 204, 0, 0.5);
        }

        footer{
            margin-top: 2rem;
            font-size: 0.85rem;
            color: #000000;
        }

        a {
            margin-top: 2rem;
            font-size: 0.85rem;
            color: #000000;
            margin-top: 20px
        }
        /* Floating elements */
        .floating {
            position: absolute;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite linear;
            z-index: -1;
        }

        @keyframes float {
            0% {
                transform: translateY(0) translateX(0) rotate(0deg);
            }
            25% {
                transform: translateY(-20px) translateX(10px) rotate(90deg);
            }
            50% {
                transform: translateY(0) translateX(20px) rotate(180deg);
            }
            75% {
                transform: translateY(20px) translateX(10px) rotate(270deg);
            }
            100% {
                transform: translateY(0) translateX(0) rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="stars" id="stars"></div>
    <div class="card">
        <h1>Welcome To Wecare API</h1>
        <p>
            This API is designed to manage <strong>desk and room reservations</strong>
            across multiple branches and resources.
            <br />Simple, fast, and reliable for your workspace needs.
        </p>
        <a href="https://we-care-khaki.vercel.app/" target="_blank" class="badge">API System Ready v2</a>
        <footer>© 2025 Wecare | Desk & Room Reservation API</footer>
        {{-- developed by ahmed hassan my-profile-cv.vercel.app --}}
        <a href="https://my-profile-cv.vercel.app/" target="_blank" class="">Developed By ENG Ahmed Hassan</a>
    </div>

    <script>
        // Create stars
        const starsContainer = document.getElementById('stars');
        const starCount = 200;

        for (let i = 0; i < starCount; i++) {
            const star = document.createElement('div');
            star.classList.add('star');

            // Random position
            const x = Math.random() * 100;
            const y = Math.random() * 100;

            // Random size
            const size = Math.random() * 3;

            // Random animation delay
            const delay = Math.random() * 5;

            star.style.left = `${x}%`;
            star.style.top = `${y}%`;
            star.style.width = `${size}px`;
            star.style.height = `${size}px`;
            star.style.animationDelay = `${delay}s`;

            starsContainer.appendChild(star);
        }

        // Create floating elements
        const floatingCount = 10;

        for (let i = 0; i < floatingCount; i++) {
            const floating = document.createElement('div');
            floating.classList.add('floating');

            // Random position
            const x = Math.random() * 100;
            const y = Math.random() * 100;

            // Random size
            const size = Math.random() * 30 + 10;

            // Random animation duration
            const duration = Math.random() * 20 + 10;

            floating.style.left = `${x}%`;
            floating.style.top = `${y}%`;
            floating.style.width = `${size}px`;
            floating.style.height = `${size}px`;
            floating.style.animationDuration = `${duration}s`;

            document.body.appendChild(floating);
        }

        // Create shooting stars
        function createShootingStar() {
            const shootingStar = document.createElement('div');
            shootingStar.classList.add('shooting-star');

            // Random starting position
            const startX = Math.random() * 50;
            const startY = Math.random() * 50;

            shootingStar.style.left = `${startX}%`;
            shootingStar.style.top = `${startY}%`;

            // Random animation duration
            const duration = Math.random() * 3 + 1;

            shootingStar.style.animation = `shoot ${duration}s linear`;

            document.body.appendChild(shootingStar);

            // Remove after animation completes
            setTimeout(() => {
                shootingStar.remove();
            }, duration * 1000);
        }

        // Create shooting stars periodically
        setInterval(createShootingStar, 2000);

        // Add initial shooting stars
        for (let i = 0; i < 3; i++) {
            setTimeout(createShootingStar, i * 500);
        }
    </script>
</body>

</html>

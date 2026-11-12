<?php require dirname(__DIR__).'/includes/app.php';
$account=require_login(); $prefix='../'; $title='About Us'; require dirname(__DIR__).'/includes/header.php'; ?>
<style>
        /* small page-specific helpers */
        .hero {
            padding: 3rem 0 1.5rem;
            text-align: center;
        }

        .team {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
            margin: 1.5rem 0 2rem;
        }

        .member {
            width: 140px;
            text-align: center;
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #ddd;
            display: inline-block;
            margin-bottom: 0.5rem;
            object-fit: cover;
        }

        .about-box {
            background: rgba(255, 255, 255, 0.9);
            padding: 1.25rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
    </style><div class="container">
        <section class="hero">
            <h2>Meet Our Team</h2>
            <p class="notice">A small team dedicated to helping students report and recover lost items quickly and safely.</p>
        </section>

        <section class="team">
            <div class="member">
                <img src="../public/image/daeniel.jpg" alt="DAENIEL RAE LABIO" class="avatar">
                <div class="status">DAENIEL RAE LABIO</div>
            </div>
            <div class="member">
                <img src="../public/image/angelene.jpg" alt="ANGELENE ALON" class="avatar">
                <div class="status">ANGELENE ALON</div>
            </div>
            <div class="member">
                <img src="../public/image/anthony.jpg" alt="ANTHONY GARCIA" class="avatar">
                <div class="status">ANTHONY GARCIA</div>
            </div>
            <div class="member">
                <img src="../public/image/jayralyn.jpg" alt="JAYRALYN GARUPIL" class="avatar">
                <div class="status">JAYRALYN GARUPIL</div>
            </div>
            <div class="member">
                <img src="../public/image/nesreen.jpg" alt="NESREEN JULAILI" class="avatar">
                <div class="status">NESREEN JULAILI</div>
            </div>
            <div class="member">
                <img src="../public/image/angel.jpg" alt="ANGEL CENABRE" class="avatar">
                <div class="status">ANGEL CENABRE</div>
            </div>
        </section>

        <section class="about-box">
            <h3>Our Mission</h3>
            <p>
                We work to provide a simple, safe place for the campus community to report and recover lost items.
                Items are logged, verified, and made available for claim so owners can be reunited with their belongings.
            </p>
        </section>
    </div>

    <?php require dirname(__DIR__).'/includes/footer.php'; ?>
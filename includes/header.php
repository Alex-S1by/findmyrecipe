


<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;

    min-height: 100vh;
}

/* Header Styles */
.topbar {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 35px;
    /* border-radius: 0 0 20px 20px; */
    position: relative;
    z-index: 10;

    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.logo {
    font-size: 2.1rem;
    font-weight: 900;
    background: black;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;


}


.auth {
    display: flex;
    gap: 20px;
    align-items: center;
}

.auth-item {
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    color: black;
    padding: 12px 20px;
    border-radius: 25px;
    transition: all 0.3s ease;
   
    border-color: black;
    border: 2px solid;
    backdrop-filter: blur(10px);
}

.auth-item:hover {



    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(209, 0, 49, 0.2);
}

.auth-item-button {
   
    color: black;
    border: none;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 10px 15px;
    gap: 8px;
    font-size: 1.5rem;
    font-weight: 500;
    cursor: pointer;
    border-radius: 25px;
    transition: all 0.3s ease;

}

.auth-item-button:hover {
    transform: translateY(-2px);
   
}



/* Sidebar Styles */
.sidebar {
    height: 100%;
    width: 0;
    position: fixed;
    z-index: 999;
    top: 0;
    right: 0;
    background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
    overflow-x: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    padding-top: 60px;
    color: white;
    box-shadow: -5px 0 20px rgba(0, 0, 0, 0.3);
}

.sidebar.open {
    width: 280px;
}
.sidebar h3 {
     font-size: 1.5rem;
    font-weight: 500;
}


.sidebar a {
    padding: 18px 30px;
    text-decoration: none;
    font-size: 1.1rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 15px;
    color: #ecf0f1;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.sidebar a:hover {
    background: rgba(52, 152, 219, 0.2);
    border-left-color: #3498db;
    padding-left: 35px;
}

.sidebar a i {
    width: 20px;
    text-align: center;
}

.closebtn {
    position: absolute;
    top: 15px;
    right: 25px;
    font-size: 2rem;
    color: #ecf0f1;
    cursor: pointer;
    transition: all 0.3s ease;
}

.closebtn:hover {
    color: #e74c3c;

}

/* Responsive Design */
@media (max-width: 768px) {
    .topbar {
        padding: 15px 20px;
        border-radius: 0 0 20px 20px;
    }

    .logo {
        font-size: 2rem;
    }

    .auth {
        gap: 10px;
    }

    .auth-item,
    .auth-item-button {
        font-size: 1rem;
        padding: 10px 15px;
    }

    .auth-item span {
        display: none;
    }
}

@media (max-width: 480px) {
    .topbar {
        padding: 10px 15px;
    }

    .logo {
        font-size: 1.8rem;
    }

    .auth-item,
    .auth-item-button {
        padding: 8px 12px;
        font-size: 0.9rem;
    }
}

/* Animation for page load */
.topbar {
    animation: slideDown 0.6s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 1;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Overlay for sidebar */
.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    transition: opacity 0.3s ease;
}

/* Hide both by default */
.sidebar-menu {
    display: none;
}

/* Show correct one based on session class */
.sidebar.user-view .user-menu {
    display: block;
}

.sidebar.admin-view .admin-menu {
    display: block;
}

.overlay.show {
    display: block;
}
</style>




<!-- Overlay -->
<div id="overlay" class="overlay" onclick="toggleSidebar()"></div>

<!-- Header -->
<nav class="topbar">
    <a style="text-decoration:none" href="/new-mini-project">
        <div class="logo">FindMyDish</div>
    </a>

    <div class="auth">
        <!-- 
            <a href="/new-mini-project/insert/insert.html" class="auth-item">
                <i class="fa-solid fa-plus"></i>
                <span>Add Recipe</span>
            </a>
            -->
        <?php if (isset($_SESSION['user']) || isset($_SESSION['admin'])): ?>
        <a href="/new-mini-project/profile" class="auth-item-button ">
            <i class="fa-solid fa-user"></i>
           
        </a>

        <button class="auth-item-button profile-toggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>

        </button>
        <?php else: ?>
        <a href="./auth/signup.php" class="auth-item">
            <i class="fa-solid fa-user-plus"></i>
            <span>Sign Up</span>
        </a>
        <a href="./auth/login.php" class="auth-item" >
            <i class="fa-solid fa-right-to-bracket"></i>
            <span>Login</span>
        </a>
        <?php endif; ?>
    </div>
</nav>



<?php if (isset($_SESSION['user']) || isset($_SESSION['admin'])): ?>
<!-- Sidebar -->
<div id="sidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="toggleSidebar()">&times;</a>

    <h3 style="padding: 35px;">
        Hi <?= htmlspecialchars($_SESSION['user'] ?? $_SESSION['admin']) ?>
    </h3>

    <?php if (isset($_SESSION['user'])): ?>
    <!-- User Menu -->
    <a href="/new-mini-project"><i class="fa fa-home"></i> Home</a>
    <a href="/new-mini-project/insert/insert.html"><i class="fa-solid fa-plus"></i> Add Recipe</a>
    <a href="/new-mini-project/favourite/"><i class="fa fa-heart"></i> Favourites</a>
    <a href="/new-mini-project/profile"><i class="fa-solid fa-user"></i> Profile</a>

    <?php endif; ?>

    <!-- Logout for both -->
    <a    onclick="confirmLogout()"  ><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>
<?php endif; ?>





 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>



function confirmLogout() {
    Swal.fire({
        title: "Are you sure?",
        text: "Do you want to logout?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e60033",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, logout",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "/new-mini-project/auth/logout.php"; // Adjust path if needed
        }
    });
}


function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");

    if (sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = 'auto';
    } else {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

// Close sidebar when clicking outside
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById("sidebar");
    const profileToggle = document.querySelector('.profile-toggle');

    if (!sidebar.contains(event.target) && !profileToggle.contains(event.target) && sidebar.classList.contains(
            'open')) {
        toggleSidebar();
    }
});

// Close sidebar on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const sidebar = document.getElementById("sidebar");
        if (sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    }
});
</script>
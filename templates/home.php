<div class="container text-center mt-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="display-4 mb-4">3D Printer Web Slicer</h1>
            <p class="lead">Professional slicing tool for Anycubic Kobra 3</p>

            <div class="mt-5">
                <button class="btn btn-primary btn-lg mx-2" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
                <button class="btn btn-success btn-lg mx-2" data-bs-toggle="modal" data-bs-target="#registerModal">
                    <i class="bi bi-person-plus"></i> Register
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно входа -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Login to your account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($error && isset($_POST['login'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="loginEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="loginPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="loginPassword" name="password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="login" class="btn btn-primary">Login</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно регистрации -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Create new account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($error && isset($_POST['register'])): ?>
                        <div class="alert <?= strpos($error, 'success') !== false ? 'alert-success' : 'alert-danger' ?>">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="registerUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="registerUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="registerEmail" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="registerEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="registerPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="registerPassword" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="registerConfirm" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="registerConfirm" name="confirm_password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="register" class="btn btn-success">Register</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
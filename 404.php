<?php
include("includes/header.php");
?>

<main class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-visual">
                <div class="film-strip">
                    <div class="film-hole"></div>
                    <div class="film-hole"></div>
                    <div class="film-hole"></div>
                    <div class="film-hole"></div>
                    <div class="film-hole"></div>
                </div>
                <div class="error-number">404</div>
                <div class="film-strip">
                    <div class="film-hole"></div>
                    <div class="film-hole"></div>
                    <div class="film-hole"></div>
                    <div class="film-hole"></div>
                    <div class="film-hole"></div>
                </div>
            </div>
            
            <div class="error-text">
                <h1>Scene Not Found</h1>
                <p>Looks like this page has been cut from the final edit. The page you're looking for doesn't exist or has been moved to another location.</p>
                
                <div class="error-actions">
                    <a href="index.html" class="btn-primary">Back to Home</a>
                    <a href="javascript:history.back()" class="btn-secondary">Go Back</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include("includes/footer.php");
?>
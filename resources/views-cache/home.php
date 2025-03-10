<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Testing Server</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(strval(assetsUrl('css/reset.css'))); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(strval(assetsUrl('css/general.css'))); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(strval(assetsUrl('css/index.css'))); ?>">
  <script defer src="<?php echo htmlspecialchars(strval(assetsUrl('/js/scripts.js'))); ?>"></script>
</head>
<body>
  <header>
    <div class="logo">
      <div class="logo-placeholder">
        <a href="<?php echo htmlspecialchars(strval(url('home'))); ?>">
          <img src="<?php echo htmlspecialchars(strval(assetsUrl('images/testingserverlogo.png'))); ?>" alt="Testingserver Logo">
        </a>
      </div>
    </div>

    <nav>
      <ul class="sidenav" id="menu-links">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <li><a href="<?php echo htmlspecialchars(strval(url('home'))); ?>">Home</a></li>
        <li><a href="<?php echo htmlspecialchars(strval(url('products'))); ?>">Products</a></li>
        <li><a href="<?php echo htmlspecialchars(strval(url('policies'))); ?>">Policies</a></li>
        <li><a href="<?php echo htmlspecialchars(strval(url('contact'))); ?>">Contact</a></li>
      </ul>
      <a class="menu-icon" onclick="openNav()"><div>&#9776;</div></a>
    </nav>

    <div class="search-container">
      <input type="text" id="product-search-bar" placeholder="Search in Testing Server"/>
      <button type="button" id="product-search-button" class="search-button">🔍</button>
    </div>

    <nav>
      <ul class="tablet-desktop">
        <li><a href="<?php echo htmlspecialchars(strval(url('home'))); ?>">Home</a></li>
        <li><a href="<?php echo htmlspecialchars(strval(url('products'))); ?>">Products</a></li>
        <li><a href="<?php echo htmlspecialchars(strval(url('policies'))); ?>">Policies</a></li>
        <li><a href="<?php echo htmlspecialchars(strval(url('contact'))); ?>">Contact</a></li>
      </ul>
    </nav>

    <div class="user-menu">
      <div id="auth-menu">
         <a data-url="<?php echo htmlspecialchars(strval(url('auth/login'))); ?>">Login</a>
         |
         <a data-url="<?php echo htmlspecialchars(strval(url('auth/sign-up'))); ?>">Sign Up</a>
      </div>
      <div class="cart-icon"><a href="<?php echo htmlspecialchars(strval(url('carts/user-cart'))); ?>">🛒</a></div>
    </div>
  </header>
  
  <main class="main-layout">
    <div class="left-sidebar"></div> <!-- Placeholder for left column -->
    <div class="homepage">
       <div id="banner">
            <img src="<?php echo htmlspecialchars(strval(assetsUrl('images/Banner.png'))); ?>" alt="Banner">
       </div>
      
       <div id="hotDeals">
          <?php $index = 0; $count = 1; $even = true; $odd = false; foreach ($topDeal as $product): ?>
            <div data-url="<?php echo htmlspecialchars(strval(url('products/' . $product->id))); ?>">
              <img src="<?php echo htmlspecialchars(strval(url($product->mainIllustrationPath))); ?>" alt="<?php echo htmlspecialchars(strval($product->name)); ?>"/>
            </div>
            <?php if ($count >= 4): ?>
              <?php break; ?>
            <?php endif; ?>
          <?php $index++; $count++; $even = !$even; $odd = !$odd; endforeach; ?>       </div>
       <div>
            <h1>ON SALE</h1>
       </div>

       <div id="onsale">
            <?php $index = 0; $count = 1; $even = true; $odd = false; foreach ($topDeal as $product): ?>
              <div class="product-card" data-url="<?php echo htmlspecialchars(strval(url('products/' . $product->id))); ?>">
                <div class="product-image">
                  <img src="<?php echo htmlspecialchars(strval(url($product->mainIllustrationPath))); ?>" alt="<?php echo htmlspecialchars(strval($product->name)); ?>"/>
                </div>
                <h4>
                  <?php echo htmlspecialchars(strval($product->name)); ?>
                </h4>
                <div class="price-container">
                  <?php if ($product->discount > 0): ?>
                    <div class="old-price-container">
                        <s class="price">
                          $<?php echo htmlspecialchars(strval($product->originalPrice)); ?>
                        </s>
                    </div>
                    <div class="new-price-container">
                        <b class="new-price">
                          $<?php echo htmlspecialchars(strval($product->price)); ?>
                        </b>
                        <span class="discount">
                          -<?php echo htmlspecialchars(strval($product->discount)); ?>%
                        </span>
                    </div>
                  <?php else: ?>                    <div class="old-price-container">
                        <p class="price">
                          $<?php echo htmlspecialchars(strval($product->price)); ?>
                        </p>
                    </div>
                  <?php endif; ?>
                </div>
                <p>
                  ⭐
                  <?php echo htmlspecialchars(strval($product->averageRating ?? '-')); ?>
                  /10
                </p>
              </div>
              <?php if ($count >= 5): ?>
                <?php break; ?>
              <?php endif; ?>
            <?php $index++; $count++; $even = !$even; $odd = !$odd; endforeach; ?>       </div>
    </div>
    <div class="right-sidebar"></div> <!-- Placeholder for right column -->
  </main>

  <footer>
    <p>&copy; Copyright 2024. All Rights Reserved.</p>
    <p><a href="mailto:testingserver@hurrcan.com.sg">testingserver@testingserver.com.sg</a></p>
  </footer>
</body>
</html>

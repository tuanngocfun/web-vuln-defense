<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(strval(assetsUrl('css/reset.css'))); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(strval(assetsUrl('css/general.css'))); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(strval(assetsUrl('css/checkout.css'))); ?>">
  <script defer src="<?php echo htmlspecialchars(strval(assetsUrl('/js/scripts.js'))); ?>"></script>
  <script defer src="<?php echo htmlspecialchars(strval(assetsUrl('/js/checkout.js'))); ?>"></script>
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
    <div class="cart-list">
      <?php if (!$cart || empty($cart->cartProducts)): ?>
        Your cart is empty. Let's go shopping!
      <?php else: ?>        <h1>Your Cart</h1>
        <?php $index = 0; $count = 1; $even = true; $odd = false; foreach ($cart->cartProducts as $cartProduct): ?>
          <?php 
            $product = $cartProduct->product;
            $total = $product->price * $cartProduct->quantity;
           ?>

          <div class="cart-container">
            <img src="<?php echo htmlspecialchars(strval(url($product->mainIllustrationPath))); ?>" alt="<?php echo htmlspecialchars(strval($product->name)); ?>">

            <div class="cart-prod-detail">
              <div class="cart-prod-name">
                <h2><?php echo htmlspecialchars(strval($product->name)); ?></h2>
              </div>
              <div class="cart-info">
                <div class="cart-price">
                  <h2>$<?php echo htmlspecialchars(strval($product->price)); ?></h2>
                </div>
                <div class="cart-quantity">
                  <h2><?php echo htmlspecialchars(strval($cartProduct->quantity)); ?></h2>
                </div>
              </div>
            </div>

            <div class="cart-item-total">
              <h2>$<?php echo htmlspecialchars(strval($total)); ?></h2>
            </div>
          </div>
        <?php $index++; $count++; $even = !$even; $odd = !$odd; endforeach; ?>  
        <div class="cart-total">
          <div class="netprice">
            Net total
          </div>

          <div class="totalprice">
            $<?php echo htmlspecialchars(strval($cart->totalPrice)); ?>
          </div>
        </div>

        <div class="cart-button">
          <a href="<?php echo htmlspecialchars(strval(url('carts/user-cart'))); ?>"><button>Back</button></a>
          <button id="confirm-cart-button">Confirm</button>
        </div>
      <?php endif; ?>
    </div>

    <div class="right-sidebar"></div> <!-- Placeholder for right column -->
  </main>

  <footer>
    <p>&copy; Copyright 2024. All Rights Reserved.</p>
    <p><a href="mailto:testingserver@hurrcan.com.sg">testingserver@testingserver.com.sg</a></p>
  </footer>
</body>
</html>

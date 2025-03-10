<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(strval(assetsUrl('css/reset.css'))); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(strval(assetsUrl('css/general.css'))); ?>">
  <script defer src="<?php echo htmlspecialchars(strval(assetsUrl('/js/scripts.js'))); ?>"></script>
  <script defer src="<?php echo htmlspecialchars(strval(assetsUrl('/js/products.js'))); ?>"></script>
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
      <input type="text" id="product-search-bar" placeholder="Search in Testing Server" value="<?php echo htmlspecialchars(strval($keyword)); ?>"/>
      <button type="button" id="product-search-button" class="search-button">🔍</button>
    </div>
    <nav>

      <ul class = "tablet-desktop">
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
    <div class="main-content">
      <!-- Filter Section -->
      <section class="filter-container">
        <h3>FILTER</h3>
        <div id="shop-filter-container" class="filter-section">
          <h4>Shops</h4>
          <?php $index = 0; $count = 1; $even = true; $odd = false; foreach ($shops as $shop): ?>
            <label><input type="checkbox" value="<?php echo htmlspecialchars(strval($shop->user->name)); ?>"/>
              <?php echo htmlspecialchars(strval($shop->user->name)); ?>
            </label><br>
          <?php $index++; $count++; $even = !$even; $odd = !$odd; endforeach; ?>        </div>
        <div id="price-filter-container" class="filter-section">
          <h4>Price</h4>
          <label><input type="checkbox" value="10" data-operator="lt"/> Less than $10</label><br>
          <label><input type="checkbox" value="10" data-value="100"/> From $10 to $100</label><br>
          <label><input type="checkbox" value="100" data-value="500"/> From $100 to $500</label><br>
          <label><input type="checkbox" value="500" data-value="1000"/> From $500 to $1000</label><br>
          <label><input type="checkbox" value="1000" data-operator="gt"/> More than $1000</label><br>
        </div>
        <div id="rating-filter-container" class="filter-section">
          <h4>Rating</h4>
          <input type="range" min="0" max="10" step="1" />
          <label><input type="radio" name="rating" value="gt"/> And Up</label><br>
          <label><input type="radio" name="rating" value="lt"/> And Down</label><br>
        </div>
      </section>
      <!-- Product Section -->
      <section class="product-section">
        <h2>
          Results for "<?php echo htmlspecialchars(strval($keyword)); ?>"
        </h2>
        <div class="searchRes">
          <p>
            <?php if ($itemCount >= 2): ?>
            <?php echo htmlspecialchars(strval($itemCount)); ?> items found
            <?php elseif ($itemCount === 1): ?>
              1 item found
            <?php else: ?>              Sorry, no item found
            <?php endif; ?>
          </p>
          <button type="button" id="clear-filters-button">Clear All Filters</button>
        </div>
        <div class="sorting">
          <label for="sort">Sort By:</label>
          <select id="sort">
            <option>Most Relevant</option>
          </select>
        </div>

        <div class="product-grid">
            <?php $index = 0; $count = 1; $even = true; $odd = false; foreach ($products as $product): ?>
              <div class="product-card" data-url="<?php echo htmlspecialchars(strval(url('products/' . $product->id))); ?>">
                <div class="product-image">
                  <img src="<?php echo htmlspecialchars(strval(url($product->mainIllustrationPath))); ?>" alt="<?php echo htmlspecialchars(strval($product->name)); ?>"/>
                </div>
                <h4><?php echo htmlspecialchars(strval($product->name)); ?></h4>
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
                    <?php else: ?>                      <div class="old-price-container">
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
            <?php $index++; $count++; $even = !$even; $odd = !$odd; endforeach; ?>        </div>
      </section>
    </div>
    <div class="right-sidebar"></div> <!-- Placeholder for right column -->
  </main>
  
  <footer>
    <p>&copy; Copyright 2024. All Rights Reserved.</p>
    <p><a href="mailto:testingserver@hurrcan.com.sg">testingserver@testingserver.com.sg</a></p>
  </footer>
</body>
</html>

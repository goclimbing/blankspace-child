<!doctype html>
<html class="no-js" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php wp_title('|', true, 'right'); ?></title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <?php wp_head(); ?>

  </head>

  <body <?php body_class(); ?>>
  <div id="wrapper">
    <header id="header" role="banner">
      <div id="header-inner" class="x-large">
        
        
 
       

           <nav id="menu" role="navigation">
            

              <?php

                wp_nav_menu(array('theme_location' => 'primary-menu'));

              ?>

         </nav>

          <nav id="mobile-nav" class="mm-menu mm-offcanvas">
        
               <?php
              wp_nav_menu(array('theme_location' => 'mobile-menu'));
                ?>


        </nav>
     </div>
</header>
<?php

if ( ! defined( 'ABSPATH' ) ) exit; // exit if accessed directly

/*
#nav-mark: TABLE OF CONTENTS

  1. HOOKS
    1.3 - add the select2 jquery script in footer
    1.2 - register the ajax function
    1.1 - register callback of the ajax function

  2. FUNCTIONS
    2.1 - wpcf_get_woo_product_data_via_ajax_js()
    2.2 - wpcf_send_woo_product_data_via_ajax()
    2.3 - wpcf_get_woo_products_to_select()
    2.4 - wpcf_compose_newsletter_admin_page()

  3. HELPERS
    3.1 - wpcf_select2_loader()
    3.2 - wpcf_create_mailchimp_campaign()
    3.3 - wpcf_set_mail_campaign_content()
    3.4 - wpcf_newsletter_template()

*/


#nav-mark: 1. HOOKS
  // 1.1
  add_action( 'in_admin_footer', 'wpcf_select2_loader' );

  // 1.2
  add_action( 'in_admin_footer', 'wpcf_get_woo_product_data_via_ajax_js' );

  // 1.3
  add_action( 'wp_ajax_wpcf_send_woo_product_data_via_ajax', 'wpcf_send_woo_product_data_via_ajax' );



#nav-mark: 2. FUNCTIONS

  // 2.1
  function wpcf_get_woo_product_data_via_ajax_js() {
    echo ('<script type="text/javascript">

      jQuery("#product_select").on("change", "select", function($) {

          var n = jQuery(this).attr("id");
          var n = n.replace("wpcf_woo_products_", "");

          var data = {
            "action": "wpcf_send_woo_product_data_via_ajax",
            "product_id": jQuery(this).val()
          };

          // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
          jQuery.post(ajaxurl, data, function(response) {
            var obj = jQuery.parseJSON( response );
            if (obj.image) {
              jQuery("#wpcf_woo_products_img_"+n).attr("src", obj.image);
            } else {
              jQuery("#wpcf_woo_products_img_"+n).attr("src", "'.plugins_url( '../images/150x150.png', __FILE__ ).'");
            }
            jQuery("#wpcf_woo_products_price_"+n).val(obj.price);
            jQuery("#wpcf_woo_products_sale_price_"+n).val(obj.sale_price);

            if (obj.product_type == "variable") {

              jQuery("#wpcf_woo_products_price_"+n).prop("disabled", true);
              jQuery("#wpcf_woo_products_sale_price_"+n).prop("disabled", true);
              jQuery("#wpcf_woo_products_sale_expiry_"+n).prop("disabled", true);
            } else {
              jQuery("#wpcf_woo_products_price_"+n).prop("disabled", false);
              jQuery("#wpcf_woo_products_sale_price_"+n).prop("disabled", false);
              jQuery("#wpcf_woo_products_sale_expiry_"+n).prop("disabled", false);
            }

            //if (obj.hasOwnProperty("sale_expiry")) {
              jQuery("#wpcf_woo_products_sale_expiry_"+n).attr("value", obj.sale_expiry);
            //}

          });
      });

      jQuery(window).bind("load", function($) {

          jQuery("#product_select select").trigger( "change" );
          jQuery("#wpcf_woo_products_0").select2({
              width: "264px"
          });

      });

    </script>');
  }

  // 2.2
  function wpcf_send_woo_product_data_via_ajax() {

      $product_id = $_REQUEST['product_id'];

      // The $_REQUEST contains all the data sent via AJAX from the Javascript call
      if ( strlen($product_id) > 0 &&  $product_id > 0) {

          $product = wc_get_product( $product_id );

          $product_data['image'] = get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' );;
          $product_data['price'] = $product->get_regular_price();
          $product_data['sale_price'] = $product->get_sale_price();
          $product_data['product_type'] = $product->get_type();
          if ($product->get_date_on_sale_to()) {
            $product_data['sale_expiry'] = $new_date = date('Y-m-d', strtotime($product->get_date_on_sale_to()));
          } else {
            $product_data['sale_expiry'] = null;
          }

          // Now let's return the result to the Javascript function (The Callback)
          echo json_encode($product_data);
      }

      // Always die in functions echoing AJAX content
     wp_die();
  }

  // 2.3
  function wpcf_get_woo_products_to_select( $add_button=false, $input_name="wpcf_woo_products",
  $input_id="wpcf_woo_products", $parent=-1, $value_field="id", $selected_value="" ) {

    // Set the arguments for the products to get
    $args = array(
      'post_type' => 'product',
      'posts_per_page' => -1
    );

    $loop = new WP_Query( $args );


    // setup our select html
    $select = '<input type="button" id="remove_product_button" class="button" onclick="RemoveSelect()" value="- Remove Product" /> <input type="button" id="add_product_button" class="button" onclick="AddProductSelect()" value="+ Add Product" /><br />
    <div id="product_select" style="max-width:930px;">
    <div style="margin:20px; float:left;" ';

    // IF $input_id was passed in
    if( strlen($input_id) ):

      // add an input id to our select html
      $id = ' id="'. $input_id .'_0" ';
      $id_div = ' id="'. $input_id .'_div_0" ';
      $id_img = ' id="'. $input_id .'_img_0" ';
      $id_price = ' id="'. $input_id .'_price_0" ';
      $id_sale_price = ' id="'. $input_id .'_sale_price_0" ';
      $id_sale_expiry = ' id="'. $input_id .'_sale_expiry_0" ';

    endif;

    $select .= $id_div.'>
    <img src="'.plugins_url( '../images/150x150.png', __FILE__ ).'" '.$id_img.' name="'. $input_name .'_img[0]" height="150" width="150" style="margin-left:60px;"><br />
    <table>
      <tr>
        <td style="padding:7px">Price:</td>
        <td style="padding:7px"><input type="text" '.$id_price.' name="'. $input_name .'_price[0]" value="0"></td>
      </tr>
      <tr>
        <td style="padding:7px">Sale Price:</td>
        <td style="padding:7px"><input type="text" '.$id_sale_price.' name="'. $input_name .'_sale_price[0]" value="0"></td>
      </tr>
      <tr>
        <td style="padding:7px">Sale Expiry:</td>
        <td style="padding:7px"><input type="date" '.$id_sale_expiry.' name="'. $input_name .'_sale_expiry[0]" /></td>
      </tr>
    </table>
    <select style="min-width:266px;" name="'. $input_name .'[0]" ';

    if( strlen($input_id) ):

      // add an input id to our select html
      $select .= 'id="'. $input_id .'_0" ';

    endif;

    // setup our first select option
    $select .= '><option value="">- Select One -</option>';

    // loop over all the pages
    // foreach ( $pages as &$page ):
    if ( $loop->have_posts() ): while ( $loop->have_posts() ): $loop->the_post();

      global $product;

      // get the page id as our default option value
      $value = $product->get_ID();

      // determine which page attribute is the desired value field
      switch( $value_field ) {
        case 'slug':
        $value = $product->get_name();
        break;
        case 'url':
        $value = get_page_link( $product->get_ID() );
        break;
        default:
        $value = $product->get_ID();
      }

      // check if this option is the currently selected option
      $selected = '';
      if( $selected_value == $value ):
        $selected = ' selected="selected" ';
      endif;

      // build our option html
      $option = '<option value="' . $value . '" '. $selected .'>';
      $option .= $product->get_name();
      $option .= '</option>';

      // append our option to the select html
      $select .= $option;

    //endforeach;
    endwhile; endif; wp_reset_postdata();

    // close our select html tag
    $select .= '</select></div></div>';

    if ($add_button == true) {
      $select .= '<script type="text/javascript">
      if (localStorage.SelectElementsNumber) {
        var n = localStorage.SelectElementsNumber-1;
        localStorage.SelectElementsNumber = "";
      } else {
        var n = 0;
      }

      document.getElementById("remove_product_button").disabled = true;

      function AddProductSelect() {
          var elmnt = document.getElementById("'. $input_id .'_div_0");
          var cln = elmnt.cloneNode(true);
          n++;
          cln.id = "'. $input_id .'_div_"+n;
          document.getElementById("product_select").appendChild(cln);

          if (n > 0) {
            document.getElementById("remove_product_button").disabled = false;
          }

          if (n == 11) {
            document.getElementById("add_product_button").disabled = true;
          }

          var elmnt_new = document.getElementById("'. $input_id .'_div_"+n);
          var select = elmnt_new.getElementsByTagName("select");

          elmnt_new.getElementsByTagName("select")[0].setAttribute("name", "'. $input_name .'["+n+"]");
          elmnt_new.getElementsByTagName("select")[0].setAttribute("id", "'. $input_id .'_"+n);
          elmnt_new.getElementsByTagName("img")[0].setAttribute("id", "'. $input_id .'_img_"+n);
          elmnt_new.getElementsByTagName("img")[0].setAttribute("name", "'. $input_name .'_img["+n+"]");
          elmnt_new.getElementsByTagName("input")[0].setAttribute("id", "'. $input_id .'_price_"+n);
          elmnt_new.getElementsByTagName("input")[0].setAttribute("name", "'. $input_name .'_price["+n+"]");
          elmnt_new.getElementsByTagName("input")[1].setAttribute("id", "'. $input_id .'_sale_price_"+n);
          elmnt_new.getElementsByTagName("input")[1].setAttribute("name", "'. $input_name .'_sale_price["+n+"]");
          elmnt_new.getElementsByTagName("input")[2].setAttribute("id", "'. $input_id .'_sale_expiry_"+n);
          elmnt_new.getElementsByTagName("input")[2].setAttribute("name", "'. $input_name .'_sale_expiry["+n+"]");

          jQuery( "#'. $input_id .'_div_"+n+" span.select2" ).remove();
          elmnt_new.getElementsByTagName("select")[0].setAttribute("class", "");
          jQuery("#'. $input_id .'_"+n).select2({
              width: "264px"
          });

          elmnt_new.getElementsByTagName("img")[0].setAttribute("src", "'.plugins_url( '../images/150x150.png', __FILE__ ).'");
          elmnt_new.getElementsByTagName("input")[0].value = "0";
          elmnt_new.getElementsByTagName("input")[1].value = "0";
          elmnt_new.getElementsByTagName("input")[2].value = "0";

      }

      function RemoveSelect(number) {
          jQuery("#product_select").children().last().remove();
          n--;

          if (n == 0) {
            document.getElementById("remove_product_button").disabled = true;
            document.getElementById("add_product_button").disabled = false;
          }

          if (n <= 12) {
            document.getElementById("add_product_button").disabled = false;
          }

          return false;
      }

      </script>';
    }

    // return our new select
    return $select;

  }

  // 2.4
  function wpcf_compose_newsletter_admin_page() {

    $step = ($_POST['step'] && (!empty($_POST['wpcf_woo_products'][0]) OR !empty($_POST['wpcf_woo_products'][1])) ) ? sanitize_text_field($_POST['step']) : 1;
    $chosen_products = $_POST['wpcf_woo_products'];

    // step 1 - chose the products
    if ($step == 1) {
      echo ('<div class="wrap">

      <h2>Newsletter Generator for WooCommerce</h2>

      <p>'.__( 'This is a simple, but yet effective Newsletter Generator for WooCommerce. Generate in a few minutes your
      newsletter, by adding some text and choosing the products to include. On the next step you can preview your result before sending it to your subscribers.', 'woo-newsletters-generator-for-mailchimp' ).'</p>

      <form action="" method="post" onsubmit="setFormSubmitting(); return confirm(\'Are you sure you wish to change the prices of those products?\');">

      <table class="form-table">

        <tbody>

        <tr>
          <th scope="row"><label for="wpcf_newsletter_subject">'.__('Newsletter Subject', 'woo-newsletters-generator-for-mailchimp').'</label></th>
          <td>
            <input type="text" class="regular-text" id="wpcf_newsletter_subject" name="wpcf_newsletter_subject" value="'.__('Read our Weekly Newsletter', 'woo-newsletters-generator-for-mailchimp').'">
            <p class="description" id="wpcf_newsletter_subject-description">'.__('Write something cool as the subject of the email that will be sent.', 'woo-newsletters-generator-for-mailchimp').'</p>
          </td>
        </tr>

        <style>
        #wp-wpcf_newsletter_small_outro-wrap,
        #wp-wpcf_newsletter_small_intro-wrap {
          max-width:930px;
        }

        </style>

        <tr>
          <th scope="row"><label for="wpcf_newsletter_small_intro">'.__('Intro', 'woo-newsletters-generator-for-mailchimp').'</label>
          <p class="description" id="wpcf_newsletter_small_intro-description">'.__('Write an intro text here to show before the products. You can leave it empty.', 'woo-newsletters-generator-for-mailchimp').'</p></th>
          <td>');

        wp_editor( null, 'wpcf_newsletter_small_intro', array('media_buttons' => false, 'textarea_name' => 'wpcf_newsletter_small_intro', 'textarea_rows' => '5') );

      echo ('</td>
        </tr>

        <tr>
          <th scope="row"><label for="wpcf_woo_products">'.__('Select Products', 'woo-newsletters-generator-for-mailchimp').'</label>
          <p class="description" id="wpcf_woo_products-description">'.__('Press "Add a Product" to add more products to the Newsletter.', 'woo-newsletters-generator-for-mailchimp').'</p></th>
          <td>
            '.wpcf_get_woo_products_to_select(true).'
          </td>
        </tr>

        <tr>
          <th scope="row"><label for="wpcf_newsletter_small_outro">'.__('Outro', 'woo-newsletters-generator-for-mailchimp').'</label>
          <p class="description" id="wpcf_newsletter_small_outro-description">'.__('Write an outro text here to show after the products. You can leave it empty.', 'woo-newsletters-generator-for-mailchimp').'</p></th>
          <td>');

        wp_editor( null, 'wpcf_newsletter_small_outro', array('media_buttons' => false, 'textarea_name' => 'wpcf_newsletter_small_outro', 'textarea_rows' => '5') );

      echo ('</td>
        </tr>

        <tr>
          <th scope="row"><label for="wpcf_newsletter_footer">'.__('Newsletter Footer', 'woo-newsletters-generator-for-mailchimp').'</label></th>
          <td>
            <input type="text" class="regular-text" id="wpcf_newsletter_footer" name="wpcf_newsletter_footer" value="'.date('Y').' &copy; '.get_bloginfo('name').'">
            <p class="description" id="wpcf_newsletter_footer-description">'.__('Write a text that will appear on the footer of the Newsletter.', 'woo-newsletters-generator-for-mailchimp').'</p>
          </td>
        </tr>

      </table>
      <input type="hidden" name="step" value="2">');

      submit_button( __('Save Products & Preview Newsletter'), 'primary', 'submit', true, array('onclick' => 'SaveProducts()') );

      //submit_button(__('Step 2 - Preview Newsletter'));

      echo('<script type="text/javascript">

      var formSubmitting = false;
      var setFormSubmitting = function() { formSubmitting = true; };

      window.onload = function() {
        if (localStorage.SelectElements) {
            document.getElementById("product_select").innerHTML = localStorage.SelectElements;
            //document.getElementById("wpcf_woo_products[0]").value = localStorage.Product0;

            var SelectValues = JSON.parse(localStorage.getItem("SelectValues"));

            var element = document.getElementById("product_select");
            var numberOfSelect = element.getElementsByTagName("select").length

            for (i = 0; i < numberOfSelect; i++) {
              if (SelectValues[i]) {
                document.getElementById("wpcf_woo_products_"+i).value = SelectValues[i];
              }
            }

            if (numberOfSelect > 1) {
              document.getElementById("remove_product_button").disabled = false;
            }

            if (numberOfSelect == 12) {
              document.getElementById("add_product_button").disabled = true;
            }


            jQuery( "span.select2" ).remove();
            jQuery( "select" ).attr("class","");
            jQuery( "select" ).select2({
                width: "264px"
            });

            localStorage.SelectElements = "";
          }


          window.addEventListener("beforeunload", function (e) {
              if (formSubmitting) {
                  return undefined;
              }

              var confirmationMessage = \'It looks like you have been editing something. \'
                                      + \'If you leave before saving, your changes will be lost.\';

              (e || window.event).returnValue = confirmationMessage; //Gecko + IE
              return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
          });


      };

      function SaveProducts() {
        if(typeof(Storage) !== "undefined") {
            var SelectElements = document.getElementById("product_select").innerHTML;
            localStorage.SelectElements = SelectElements;

            var element = document.getElementById("product_select");
            var numberOfSelect = element.getElementsByTagName("select").length
            localStorage.SelectElementsNumber = numberOfSelect;

            var SelectValues = [];

            for (i = 0; i < numberOfSelect; i++) {
              var CurValue = document.getElementById("wpcf_woo_products_"+i).value;
              if (CurValue) {
                SelectValues.push(CurValue);
              }
            }

            localStorage.setItem("SelectValues", JSON.stringify(SelectValues));

        }
      }
      </script>
      </form>
      </div>');
    }

    // step 2 - preview the message
    if ($step == 2) {

      $chosen_products = $_POST['wpcf_woo_products'];
      $product_prices = $_POST['wpcf_woo_products_price'];
      $product_sale_prices = $_POST['wpcf_woo_products_sale_price'];
      $product_sale_expiry = $_POST['wpcf_woo_products_sale_expiry'];

      $n = 0;

      // save product data
      foreach ($chosen_products as $product_id) {

        if ((float)$product_prices[$n] > 0) {
          update_post_meta($product_id, '_regular_price', (float)$product_prices[$n]);
          update_post_meta($product_id, '_price', (float)$product_prices[$n]);
        }
        if ((float)$product_sale_prices[$n] > 0) {
          update_post_meta($product_id, '_sale_price', (float)$product_sale_prices[$n]);
          update_post_meta($product_id, '_price', (float)$product_prices[$n]);
        } else {
          delete_post_meta($product_id, '_sale_price');
        }
        if (isset($product_sale_expiry[$n])) {
          update_post_meta($product_id, '_sale_price_dates_to', strtotime($product_sale_expiry[$n]));
        }

        $n++;

      }

      echo ('<div class="wrap">

      <h2>Newsletter Generator for WooCommerce - Step 2</h2>

      <p>Preview the Newsletter and press "Send with MailChimp" to send it to your Subscribers or "Back to Generator" to make changes.</p>

      <form action="" method="post" onSubmit="return confirm(\'Are you sure you wish to create & send the Campaign?\');">');

      $args = array(
      'post_type' => 'product',
      'posts_per_page' => -1,
      'orderby' => 'post__in',
      'post__in' => $chosen_products,

      );

      // echo the loop for preview
      $tpl_args = array(
        'subject' => $_POST['wpcf_newsletter_subject'],
        'intro' => $_POST['wpcf_newsletter_small_intro'],
        'outro' => $_POST['wpcf_newsletter_small_outro'],
        'footer_text' => $_POST['wpcf_newsletter_footer'],
      );

      $newsletter = wpcf_newsletter_template('header', $tpl_args);
      $n = 0; // start the count

      $loop = new WP_Query( $args );
      if ( $loop->have_posts() ): while ( $loop->have_posts() ): $loop->the_post();

      global $product;

      // add products in the loop
      $newsletter .= wpcf_newsletter_template('product',$product);

      echo '<input type="hidden" name="wpcf_woo_products['.$n.']" value="'.$product->get_ID().'">';
      $n++;

      endwhile; endif; wp_reset_postdata();

      $newsletter .= wpcf_newsletter_template('footer', $tpl_args); // end the newsletter by attaching the footer
      echo $newsletter;

      echo '<input type="hidden" name="wpcf_newsletter_subject" value="'.$_POST['wpcf_newsletter_subject'].'">';
      echo '<input type="hidden" name="wpcf_newsletter_small_intro" value="'.$_POST['wpcf_newsletter_small_intro'].'">';
      echo '<input type="hidden" name="wpcf_newsletter_small_outro" value="'.$_POST['wpcf_newsletter_small_outro'].'">';



      submit_button('Send with Mailchimp', 'primary', 'submit', false);
      echo " ";
      submit_button('Back to Generator', 'secondary', 'clear', false, array( 'onClick' => 'history.back();return false;') );

      echo ('<input type="hidden" name="step" value="3">
      </form>
      </div>');
    }


    // step 3 - send the message
    if ($step == 3) {

      echo ('<div class="wrap">

      <h2>Newsletter Generator for WooCommerce</h2>

      <p>That\'s it, your message has been sent succesfully.</p>');

      $args = array(
      'post_type' => 'product',
      'posts_per_page' => -1,
      'orderby' => 'post__in',
      'post__in' => $chosen_products,

      );

      $tpl_args = array(
        'subject' => $_POST['wpcf_newsletter_subject'],
        'intro' => $_POST['wpcf_newsletter_small_intro'],
        'outro' => $_POST['wpcf_newsletter_small_outro'],
        'footer_text' => $_POST['wpcf_newsletter_footer'],
      );

      $newsletter = wpcf_newsletter_template('header', $tpl_args); // start creating the loop with products

      $loop = new WP_Query( $args );
      if ( $loop->have_posts() ): while ( $loop->have_posts() ): $loop->the_post();

      global $product;
      global $woocommerce;

      // add products in the loop
      $newsletter .= wpcf_newsletter_template('product',$product);

      endwhile; endif; wp_reset_postdata();

      $newsletter .= wpcf_newsletter_template('footer', $tpl_args); // end the newsletter by attaching the footer

      /*
      ** Find more information here:
      ** https://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/content/#%20
      ** http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/#%20
      */

      $campaign_id = wpcf_create_mailchimp_campaign( get_option( 'wpcf_mailchimp_list_id' ), $tpl_args['subject'] );

      if ( $campaign_id ) {

          // Set the content for this campaign
          $template_content = array(
            'html' => $newsletter,
          );

          $set_campaign_content = wpcf_set_mail_campaign_content( $campaign_id, $template_content );

          // Send the Campaign if the content was set.
          // NOTE: Campaign will send immediately.
          if ( $set_campaign_content ) {

              $send_campaign = wpcf_mailchimp_api_request( "campaigns/$campaign_id/actions/send", 'POST' );
              if ( empty( $send_campaign ) ) {
                  // Campaign was sent!
              } elseif( isset( $send_campaign->detail ) ) {
                  $error_detail = $send_campaign->detail;
              }

          }

      }


    } // step 3 end

  } // function end



#nav-mark: 3. HELPERS

  // 3.1
  function wpcf_select2_loader() {
    echo '<link href="'.plugins_url( '../css/select2.min.css', __FILE__ ).'" rel="stylesheet" />
    <script src="'.plugins_url( '../js/select2.min.js', __FILE__ ).'"></script>';
  }

  // 3.2
  function wpcf_create_mailchimp_campaign( $list_id, $subject ) {

      // Configure --------------------------------------

      $reply_to   = get_option('admin_email');
      $from_name  = get_bloginfo( 'name' );

      // STOP Configuring -------------------------------

      $campaign_id = '';

      $body = array(
          'recipients'    => array('list_id' => $list_id),
          'type'          => 'regular',
          'settings'      => array('subject_line' => $subject,
                                  'reply_to'      => $reply_to,
                                  'from_name'     => $from_name
                                  )
      );

      $create_campaign = wpcf_mailchimp_api_request( 'campaigns', 'POST', $body );

      if ( $create_campaign ) {
          if ( ! empty( $create_campaign->id ) && isset( $create_campaign->status ) && 'save' == $create_campaign->status ) {
              // The campaign id:
              $campaign_id = $create_campaign->id;
          }
      }

      return $campaign_id ? $campaign_id : false;

  }

  // 3.3
  function wpcf_set_mail_campaign_content( $campaign_id, $template_content ) {
      $set_content = '';
      $set_campaign_content = wpcf_mailchimp_api_request( "campaigns/$campaign_id/content", 'PUT', $template_content );

      if ( $set_campaign_content ) {
          if ( ! empty( $set_campaign_content->html ) ) {
              $set_content = true;
          }
      }
      return $set_content ? true : false;
  }

  // 3.4
  function wpcf_newsletter_template($template,$content=null) {

    global $woocommerce;

    if ($template == 'product' && !empty($content)) {

      $product = $content;

      if ($product->is_on_sale()) {
        if($product->product_type=='variable') {
          $available_variations = $product->get_available_variations();

          $variable_min_price = $available_variations[0]['display_price'];
          $variable_max_price = $available_variations[0]['display_price'];

          foreach ($available_variations as $variable) {
            if ($variable['display_price'] < $variable_min_price) {$variable_min_price = $variable['display_price'];}
            if ($variable['display_price'] > $variable_max_price) {$variable_max_price = $variable['display_price'];}
          }

          if ($variable_min_price !== $variable_max_price) {
            $price .= "<p><span style='color:#111; font-weight:bold;'>Από ".wc_price($variable_min_price)." έως ".wc_price($variable_max_price)."</span> </p>";
          } else {
            $price .= "<p><span style='color:#111; font-weight:bold;'>".wc_price($variable_min_price)."</span> </p>";
          }
        } else {
          //$price .= "<p><span style='color:#111; font-weight:bold;'>".wc_price($product->get_price())."</span></p>";
          $price .= "<p><span style='text-decoration: line-through; opacity: .6;'>".wc_price($product->get_price())."</span> <span style='color:#111; font-weight:bold;'>".wc_price($product->get_regular_price()).'</span></p>';
        }
      } else {
        if($product->product_type=='variable') {
          $available_variations = $product->get_available_variations();

          $variable_min_price = $available_variations[0]['display_price'];
          $variable_max_price = $available_variations[0]['display_price'];

          foreach ($available_variations as $variable) {
            if ($variable['display_price'] < $variable_min_price) {$variable_min_price = $variable['display_price'];}
            if ($variable['display_price'] > $variable_max_price) {$variable_max_price = $variable['display_price'];}
          }

          if ($variable_min_price !== $variable_max_price) {
            $price .= "<p><span style='color:#111; font-weight:bold;'>Από ".wc_price($variable_min_price)." έως ".wc_price($variable_max_price)."</span> </p>";
          } else {
            $price .= "<p><span style='color:#111; font-weight:bold;'>".wc_price($variable_min_price)."</span> </p>";
          }
        } else {
          $price .= "<p><span style='color:#111; font-weight:bold;'>".wc_price($product->get_price())."</span></p>";
          //$price .= "<p><span style='text-decoration: line-through; opacity: .6;'>".wc_price($product->get_price())."</span> <span style='color:#111; font-weight:bold;'>".wc_price($product->get_regular_price()).'</span></p>';
        }
      }

      // the product template follows
      $output = '<table width="192" border="0" cellpadding="0" cellspacing="0" align="left" class="force-row" style="width: 192px;">
                <tr>
                  <td class="col" valign="top" style="padding-left:12px;padding-right:12px;padding-top:18px;padding-bottom:12px">
                    <table border="0" cellpadding="0" cellspacing="0" class="img-wrapper">
                      <tr>
                        <td style="padding-bottom:18px">'.$product->get_image(array('168','168')).'</td>
                      </tr>
                    </table>
                    <table border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td class="" style="font-family:Helvetica, Arial, sans-serif;font-size:16px;line-height:22px;font-weight:600;color:#2469A0; height:43px;">'.$product->get_name().'</td>
                      </tr>
                    </table>
                    <div class="col-copy" style="font-family:Helvetica, Arial, sans-serif;font-size:13px;line-height:20px;text-align:left;color:#333333">'.$price.'
                    <a href="'.$product->get_permalink().'" style="background-color:#FC461E;-moz-border-radius:4px;-webkit-border-radius:4px;border-radius:4px;
                    border:1px solid #ffffff;display:inline-block;cursor:pointer;color:#ffffff;font-family:Arial;font-size:15px;padding:5px 15px;text-decoration:none;
                    ">Αγόρασε το</a></div>
                    <br>
                  </td>
                </tr>
              </table>';

      return $output;

    } elseif ($template == 'header') {

      // newsletter header template
      $output = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
      <html xmlns="http://www.w3.org/1999/xhtml"
       xmlns:v="urn:schemas-microsoft-com:vml"
       xmlns:o="urn:schemas-microsoft-com:office:office">
      <head>
        <!--[if gte mso 9]><xml>
         <o:OfficeDocumentSettings>
          <o:AllowPNG/>
          <o:PixelsPerInch>96</o:PixelsPerInch>
         </o:OfficeDocumentSettings>
        </xml><![endif]-->
        <!-- fix outlook zooming on 120 DPI windows devices -->
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- So that mobile will display zoomed in -->
        <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- enable media queries for windows phone 8 -->
        <meta name="format-detection" content="date=no"> <!-- disable auto date linking in iOS 7-9 -->
        <meta name="format-detection" content="telephone=no"> <!-- disable auto telephone linking in iOS 7-9 -->

        <style type="text/css">
      body {
        margin: 0;
        padding: 0;
        -ms-text-size-adjust: 100%;
        -webkit-text-size-adjust: 100%;
      }

      table {
        border-spacing: 0;
      }

      table td {
        border-collapse: collapse;
      }

      .ExternalClass {
        width: 100%;
      }

      .ExternalClass,
      .ExternalClass p,
      .ExternalClass span,
      .ExternalClass font,
      .ExternalClass td,
      .ExternalClass div {
        line-height: 100%;
      }

      .ReadMsgBody {
        width: 100%;
        background-color: #ebebeb;
      }

      table {
        mso-table-lspace: 0pt;
        mso-table-rspace: 0pt;
      }

      img {
        -ms-interpolation-mode: bicubic;
      }

      .yshortcuts a {
        border-bottom: none !important;
      }

      @media screen and (max-width: 599px) {
        .force-row,
        .container {
          width: 100% !important;
          max-width: 100% !important;
        }
      }
      @media screen and (max-width: 400px) {
        .container-padding {
          padding-left: 12px !important;
          padding-right: 12px !important;
        }
      }
      .ios-footer a {
        color: #aaaaaa !important;
        text-decoration: underline;
      }

      @media screen and (max-width: 599px) {
        .col {
          width: 100% !important;
          border-top: 1px solid #eee;
          padding-bottom: 0 !important;
        }

        .cols-wrapper {
          padding-top: 18px;
        }

        .img-wrapper {
          float: right;
          max-width: 40% !important;
          height: auto !important;
          margin-left: 12px;
        }

        .subtitle {
          margin-top: 0 !important;
        }
      }
      @media screen and (max-width: 400px) {
        .cols-wrapper {
          padding-left: 0 !important;
          padding-right: 0 !important;
        }

        .content-wrapper {
          padding-left: 12px !important;
          padding-right: 12px !important;
        }
      }
      a[href^="x-apple-data-detectors:"],
      a[x-apple-data-detectors] {
        color: inherit !important;
        text-decoration: none !important;
        font-size: inherit !important;
        font-family: inherit !important;
        font-weight: inherit !important;
        line-height: inherit !important;
      }
      </style>
      </head>

      <body style="margin:0; padding:0;" bgcolor="#F0F0F0" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">

      <!-- 100% background wrapper (grey background) -->
      <table border="0" width="100%" height="100%" cellpadding="0" cellspacing="0" bgcolor="#F0F0F0">
        <tr>
          <td align="center" valign="top" bgcolor="#F0F0F0" style="background-color: #F0F0F0;">

            <br>

            <!-- 600px container (white background) -->
            <table border="0" width="600" cellpadding="0" cellspacing="0" class="container" style="width:600px;max-width:600px">
              <tr>
                <td class="container-padding header" align="left" style="font-family:Helvetica, Arial, sans-serif;font-size:24px;font-weight:bold;padding-bottom:12px;color:#DF4726;padding-left:24px;padding-right:24px">
                  <a href="'.get_bloginfo('url').'" target="_blank" style="text-decoration:none; color:#DF4726;">%blogname%</a>
                </td>
              </tr>
              <tr>
                <td class="content" align="left" style="padding-top:12px;padding-bottom:12px;background-color:#ffffff">

      <table width="600" border="0" cellpadding="0" cellspacing="0" class="force-row" style="width: 600px;">
        <tr>
          <td class="content-wrapper" style="padding-left:24px;padding-right:24px">
            <br>
            <div class="title" style="font-family:Helvetica, Arial, sans-serif;font-size:18px;font-weight:600;color:#374550">%subject%</div>
          </td>
        </tr>
        <tr><td class="cols-wrapper" style="padding:0 25px;"><p>%intro%</p></td></tr>
        <tr>
          <td class="cols-wrapper" style="padding-left:12px;padding-right:12px">';

      if (get_option(wpcf_newsletter_logo)) {
        $image = '<img style="margin:20px 0;" src="'.get_option('wpcf_newsletter_logo').'">';
        $output = str_replace('%blogname%', $image, $output );
      } else {
        $output = str_replace('%blogname%', '<br /><br />'.get_bloginfo('name').'<br /><br />', $output );
      }
      $output = str_replace('%subject%', $content['subject'], $output );
      $output = str_replace('%intro%', $content['intro'], $output );

      return $output;

    } elseif ($template == 'footer') {

      // newsletter footer template
      $output = '</td>
    </tr>
    <tr><td class="cols-wrapper" style="padding:0 25px;"><p>%outro%</p></td></tr>
    </table>

              </td>
            </tr>
            <tr>
              <td class="container-padding footer-text" align="left" style="font-family:Helvetica, Arial, sans-serif;font-size:12px;line-height:16px;color:#aaaaaa;padding-left:24px;padding-right:24px">
                <br>
                %footer_text%
                <br><br><br>
              </td>
            </tr>
          </table>
    <!--/600px container -->


        </td>
      </tr>
    </table>
    <!--/100% background wrapper-->

    </body>
    </html>';
    $output = str_replace('%outro%', $content['outro'], $output );
    $output = str_replace('%footer_text%', $content['footer_text'], $output );
    return $output;

    } else {

      // return false if no template selected
      return false;


    }
  }

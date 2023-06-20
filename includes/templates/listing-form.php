

<div id="form_success" style="background-color:green; color:#fff;"></div>
<div id="form_error" style="background-color:red; color:#fff;"></div>

<form id="listing_form">


      <?php wp_nonce_field('wp_rest');?>

      <label>Name</label><br />
      <input type="text" name="name"><br /><br />

      <label>City</label><br />
      <input type="text" name="city"><br /><br />

      <label>Country</label><br />
      <input type="text" name="country"><br /><br />

      <label>State</label><br />
      <input name="state"></textarea><br /><br />

      <button type="submit">Submit form</button>

</form>

<script>
    jQuery(document).ready(function($){
            $("#listing_form").submit( function(event){

                  event.preventDefault();

                  $("#form_error").hide();

                  var form = $(this);

                  $.ajax({
                        type:"POST",
                        url: "<?php echo get_rest_url(null, 'listing-form/submit');?>",
                        data: form.serialize(),
                        success:function(res){

                              form.hide();

                              $("#form_success").html(res).fadeIn();

                        },
                        error: function(err){
                            console.log(err);
                              $("#form_error").html("There was an error submitting").fadeIn();
                        }


                  })


            });


      });

</script>
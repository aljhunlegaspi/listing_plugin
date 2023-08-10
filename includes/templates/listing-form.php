

<div id="form_success" style="background-color:green; color:#fff;"></div>
<div id="form_error" style="background-color:red; color:#fff;"></div>

<form id="listing_form">


      <?php wp_nonce_field('wp_rest');?>

      <label>Subject</label><br />
      <select id="subject" name="subject">
            <option value="volvo">Volvo</option>
            <option value="saab">Saab</option>
            <option value="fiat">Fiat</option>
            <option value="audi">Audi</option>
      </select>

      <label>City</label><br />
      <textarea type="text" name="city"></textarea>

      <button type="submit">Submit form</button>

</form>

<script>
    jQuery(document).ready(function($){
            $("#listing_form").submit( function(event){

                  event.preventDefault();

                  $("#form_error").hide();

                  var form = $(this);
                  console.log(form.serialize(), 'form.serialize()')
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
                              // $("#form_error").html("There was an error submitting").fadeIn();
                        }


                  })


            });


      });

</script>
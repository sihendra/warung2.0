/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jQuery.validator.addMethod("mustselect", function(value) {
		return value.toString().indexOf("--- ") == -1;
	}, 'This field is required');

jQuery(document).ready(function() {
    jQuery("#wCart_shipping_form2").validate({
        rules: {
            semail: {// compound rule
                required: true,
                email: true
            },
            sphone: {
                required: true,
                number: true
            },
            scity: "required",
            sname: "required",
            saddress: "required",
            spaymentmethod: "required",
            spostalcode: "required"
        }
        /*
        ,messages: {
        semail: "Tolong isi field ini."
        }
        */

    });
//
//    jQuery("#wCart_confirmation").validate({
//        submitHandler: function(form) {
//            if (confirm("Apakah data sudah benar?")) {
//                form.submit();
//            }
//        }
//    })
});


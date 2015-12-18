/* An example of an iframe:

<div id="iframe-payment-container" style="border: 0px; min-width: 460px; min-height: 325px">
  <iframe width="460" height="325" style="border: 0px; height: 325; width: 460"></iframe>
</div>

N.B. iframe-payment-container is used as a placeholder during the time when the iframe is expanded. 
*/

var shrinkIframe = function(iframe, iframe_data) {
  iframe.css(iframe_data);
  jQuery("#dimmed_background_box").remove();
}; 
var expandIframe = function() {
  var iframe_data = {
    position: iframe.attr("position") || "static",
    top: iframe.position().top,
    left: iframe.position().left,
    width: iframe.width(),
    height: iframe.height(),
    zIndex: iframe.attr("zIndex"),
    marginLeft: iframe.attr("marginLeft"),
    marginRight: iframe.attr("marginRight")
  };

  jQuery('body').append("<div id='dimmed_background_box'></div>");
  jQuery('#dimmed_background_box').css({ height:'100%',width:'100%',position:'fixed',top:0,left:0,zIndex:9998,backgroundColor:'#000000',opacity:0.5 });

  var window_height = jQuery(window).height();
  var window_width = jQuery(window).width();

  if (window_width < 960) {
    iframe.css({ height:window_height,width:window_width,top:0 });
  } else {
    iframe.css({ height:640,width:960,top:(window_height-640)/2 });
  }
  iframe.css({ position:'fixed',zIndex:9999,margin:'auto' });
  return iframe_data;
};

var shrinked_iframe_data;
var iframe = jQuery('#iframe-payment-container iframe'); // iframe selector should be used

window.addEventListener('message', function(event) {
  if (event.origin !== "https://igw-demo.every-pay.com" && event.origin !== "https://pay.every-pay.eu") { return; }

  var message = JSON.parse(event.data);
  /* 
  1. An "expand" message is sent from the iframe page when 3D secure page is going to be displayed.
     The size of the iframe should be adjusted to hold 3D secure page
  2. A "shrink" message is sent from the iframe page when a user has provided authorisation details on the 3D secure page.
     The size of the iframe should be set to the initial values
  */
  if (message.resize_iframe == "expand") {
    shrinked_iframe_data = expandIframe(iframe);
  } else if (message.resize_iframe == "shrink") {
    shrinkIframe(iframe, shrinked_iframe_data);
  }

  // An example of a possible way to process message data. Note, all fields, except transaction_result, are locale dependent - 
  // the texts are returned in the language specified in the API request locale parameter.
  //
  // if (message.transaction_result) {
  //   jQuery('.transaction_result').append(message.transaction_result); // Transaction's result. Possible states: completed, failed.
  //   jQuery('.message_title').append(message.message_title);           // Contains payment result page title - "Thank you! Payment successful." or "Sorry, payment was unsuccessful."
  //   jQuery('.message_error').append(message.message_error);           // Contains information about the cause of payment's failure, if the exact reason is known and can be revealed to the buyer.
  //   jQuery('.message_action').append(message.message_action);         // Contains suggestions about what the customer should do to be able to perform a successful payment.
  //   jQuery('.message_contact').append(message.message_contact);       // Contains instructions about who the customer should contact if the problem persists.
  // }
  //
}, false);
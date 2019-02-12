/*

// USAGE
// ======
//
// 0. Include jQuery

// 1. Include this file (e.g. <script src="...">)

// 2.Assign selector to the iframe
EveryPay.iframe = jQuery('#iframe-payment-container iframe')

// 3. Assign callback to get transaction result from EveryPay message
EveryPay.messageCallback = function(message){
  // An example of a possible way to process message data. Note, all fields, except transaction_result, are locale dependent -
  // the texts are returned in the language specified in the API request locale parameter.
  if (message.transaction_result) {
    jQuery('.transaction_result').append(message.transaction_result); // Transaction's result. Possible states: completed, failed.
    jQuery('.message_title').append(message.message_title);           // Contains payment result page title - "Thank you! Payment successful." or "Sorry, payment was unsuccessful."
    jQuery('.message_error').append(message.message_error);           // Contains information about the cause of payment's failure, if the exact reason is known and can be revealed to the buyer.
    jQuery('.message_action').append(message.message_action);         // Contains suggestions about what the customer should do to be able to perform a successful payment.
    jQuery('.message_contact').append(message.message_contact);       // Contains instructions about who the customer should contact if the problem persists.
  }
}

// An example of an iframe
// N.B. iframe-payment-container is used as a placeholder during the time when the iframe is expanded.
<div id="iframe-payment-container" style="border: 0px; min-width: 460px; min-height: 325px">
  <iframe width="460" height="325" style="border: 0px; height: 325; width: 460"></iframe>
</div>

*/
var EveryPay = {
  shrinkIframe: function(iframe) {
    iframe.css(this.shrinkedIframeStyle);
    jQuery("#dimmed_background_box").remove();
  },

  expandIframe: function(iframe) {
    this.shrinkedIframeStyle = {
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

    if (jQuery(window).width() < 960) {
      iframe.css({ height:jQuery(window).height(),width:jQuery(window).width(),top:0 });
    } else {
      iframe.css({ height:640,width:960,top:(jQuery(window).height()-640)/2 });
    }
    iframe.css({ position:'fixed',zIndex:9999,margin:'auto' });
  },

  messageHandler: function(event) {
    if (event.origin !== "https://igw-demo.every-pay.com" && event.origin !== "https://pay.every-pay.eu") { return; }

    var message = JSON.parse(event.data);
    /*
    1. An "expand" message is sent from the iframe page when 3D secure page is going to be displayed.
      The size of the iframe should be adjusted to hold 3D secure page
    2. A "shrink" message is sent from the iframe page when a user has provided authorisation details on the 3D secure page.
      The size of the iframe should be set to the initial values
    */
    if (message.resize_iframe == "expand") {
      this.expandIframe(this.iframe);
    } else if (message.resize_iframe == "shrink") {
      this.shrinkIframe(this.iframe);
    }

    if (typeof this.messageCallback === "function"){
      this.messageCallback(message)
    }
  },

  shrinkedIframeStyle: "",
  messageCallback: null,
  iframe: jQuery('#iframe-payment-container iframe'), // assign jQuery selector to the iframe
};

// Add event listener to the 'message' event
window.addEventListener('message', EveryPay.messageHandler.bind(EveryPay), false)



function mysql_datetime() {
	return (new Date()).toISOString().slice(0, 19).replace('T', ' ');
}

window.performance = window.performance || {};
performance.now = (function () {
	return performance.now ||
			performance.mozNow ||
			performance.msNow ||
			performance.oNow ||
			performance.webkitNow;
})();

function flatStringifyGeo(geo) {
	var result = {};
	result.timestamp = geo.timestamp;
	var coords = {};
	coords.accuracy = geo.coords.accuracy;
	coords.altitude = geo.coords.altitude;
	coords.altitudeAccuracy = geo.coords.altitudeAccuracy;
	coords.heading = geo.coords.heading;
	coords.latitude = geo.coords.latitude;
	coords.longitude = geo.coords.longitude;
	coords.speed = geo.coords.speed;
	result.coords = coords;
	return JSON.stringify(result);
}

function general_alert(message, place) {
	$(place).append(message);
}

function bootstrap_alert(message, bold, where, cls) {
	cls = cls || 'alert-danger';
	where = where || '.alerts-container';
	var $alert = $('<div class="row"><div class="col-lg-12 all-alerts"><div class="alert ' + cls + '"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>' + (bold ? bold : 'Problem') + '</strong> ' + message + '</div></div></div>');
	$alert.prependTo($(where));
	$alert[0].scrollIntoView(false);
}

function bootstrap_modal(header, body, t) {
	t = t || 'tpl-feedback-modal';
	var $modal = $($.parseHTML(getHTMLTemplate(t, {'body': body, 'header': header})));
	$modal.modal('show').on('hidden.bs.modal', function () {
		$modal.remove();
	});
	return $modal;
}

function bootstrap_spinner() {
	return ' <i class="fa fa-spinner fa-spin"></i>';
}

function ajaxErrorHandling(e, x, settings, exception) {
	var message;
	var statusErrorMap = {
		'400': "Server understood the request but request content was invalid.",
		'401': "You don't have access.",
		'403': "You were logged out while coding, please open a new tab and login again. This way no data will be lost.",
		'404': "Page not found.",
		'500': "Internal Server Error.",
		'503': "Server can't be reached."
	};
	if (e.status) {
		message = statusErrorMap[e.status];
		if (!message) {
			message = (typeof e.statusText !== 'undefined' && e.statusText !== 'error') ? e.statusText : 'Unknown error. Check your internet connection.';
		}
	} else if (e.statusText === 'parsererror') {
		message = "Parsing JSON Request failed.";
	} else if (e.statusText === 'timeout') {
		message = "The attempt to save timed out. Are you connected to the internet?";
	} else if (e.statusText === 'abort') {
		message = "The request was aborted by the server.";
	} else {
		message = (typeof e.statusText !== 'undefined' && e.statusText !== 'error') ? e.statusText : 'Unknown error. Check your internet connection.';
	}

	if (e.responseText) {
		var resp = $(e.responseText);
		resp = resp.find(".alert").addBack().filter(".alert").html();
		message = message + "<br>" + resp;
	}

	bootstrap_alert(message, 'Error.', '.alerts-container');
}

function stringTemplate(string, params) {
	for (var i in params) {
		var t = "%?\{" + i + "\}";
		string = string.replace((new RegExp(t, 'g')), params[i]);
	}
	return string;
}

function getHTMLTemplate(id, params) {
	var $tpl = jQuery('#' + id);
	if (!$tpl.length)
		return;
	return stringTemplate($.trim($tpl.html()), params);
}

function toggleElement(id) {
	$('#' + id).toggleClass('hidden');
}

function download(filename, text) {
	var element = document.createElement('a');
	element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
	element.setAttribute('download', filename);

	element.style.display = 'none';
	document.body.appendChild(element);

	element.click();

	document.body.removeChild(element);
}

function download_next_textarea(link) {
	var $link = $(link);
	download($link.data("filename"), $link.parent().find("textarea").val());
	return false;
}

function cookies_enabled() {
	try {
		document.cookie = 'cookietest=1';
		var ret = document.cookie.indexOf('cookietest=') != -1;
		document.cookie = 'cookietest=1; expires=Thu, 01-Jan-1970 00:00:01 GMT';
		return ret;
	} catch (e) {
		return false;
	}
}

// Configure cookie-consent script
		window.gccConfig = {
			content: {
				message: "On our website we're using cookies to optimize user experience and to improve our website. " +
				"By using our website you agree that cookies can be stored on your local computer.",
			},
			palette: {
				popup: {background: '#333333', text: '#fff', link: '#fff'},
				button: {background: "#8dc63f", text: '#fff'}
			}
		};

(function ($) {
	"use strict";

	/**
	 * formr.org main.js
	 * @requires jQuery, webshim
	 */

	// polyfill window.performance.now

	/**
	 * jQuery Plugin: Sticky Accordion and Tabs
	 *
	 */
	(function ($) {
		$.fn.stickyStuff = function () {
			var context = this;
			// Show the tab/collapsible corresponding with the hash in the URL, or the first tab (if the collapsible is inside a tab, show that too).
			var showStuffFromHash = function () {
				var hash = window.location.hash;
				var selector = hash ? 'a[href="' + hash + '"]' : 'li.active > a';
				if ($(selector, context).data('toggle') === "tab") {
					$(selector, context).tab('show');
				} else if ($(selector, context).data('toggle') === "collapse") {
					var collapsible = hash;
					$(collapsible, context).collapse("show");
					var parent_tab = $(collapsible, context).parents('.tab-pane');
					if (parent_tab && !parent_tab.hasClass("active")) {
						$('a[href=#' + parent_tab.attr('id') + ']').tab('show');
					}
				}
			};


			// Set the correct tab when the page loads
			showStuffFromHash(context);

			// Set the correct tab when a user uses their back/forward button
			$(window).on('hashchange', function () {
				showStuffFromHash(context);
			});

			// Change the URL when tabs are clicked
			$('a', context).on('click', function (e) {
				history.pushState(null, null, this.href);
				showStuffFromHash(context);
			});

			return this;
		};
	}(jQuery));

	$(function () { // on domready
		if ($(".schmail").length == 1) {
			var schmail = $(".schmail").attr('href');
			schmail = schmail.replace("IMNOTSENDINGSPAMTO", "").
					replace("that-big-googly-eyed-email-provider", "gmail").
					replace(encodeURIComponent("If you are not a robot, I have high hopes that you can figure out how to get my proper email address from the above."), "").
					replace(encodeURIComponent("\r\n\r\n"), "");
			$(".schmail").attr('href', schmail);
		}

		$('*[title]').tooltip({
			container: 'body'
		});

		hljs.initHighlighting();
		$('.nav-tabs, .tab-content').stickyStuff();

		// hammer time
		$(".navbar-toggle").attr("style", "-ms-touch-action: manipulation; touch-action: manipulation;");
		// Higlight current menu item
		$('ul.menu-highlight a').each(function () {
			var $a = $(this);
			var href = $a.attr('href');
			if (href === document.location.href) {
				$a.parents('li').addClass('active');
			}
		});

		// Social share button click
		$('.social-share-icon').unbind('click').bind('click', function () {
			var $social = $(this), href = $social.attr('data-href');
			if (href) {
				if ($social.attr('data-target')) {
					window.open(href, $social.attr('data-target'), $social.attr('data-width') ? 'width=' + $social.attr('data-width') + ',height=' + $social.attr('data-height') : undefined);
				} else {
					window.location.href = href;
				}
			}
		});
		// Activate clipboard copy links
		$('.copy_clipboard').click(function () {
			this.select();

			try {
				var copysuccessful = document.execCommand('copy');
				if (copysuccessful) {
					$(this).tooltip({title: "Link was copied to clipboard.", position: 'top'}).tooltip('show');
				}
			} catch (err) {
			}
		});
		// Activate .copy-url anchor tags
		$('.copy-url').click(function () {
			try {
				var url = $(this).data('url');
				var dummy = document.createElement('input');
				document.body.appendChild(dummy);
				dummy.value = url;
				dummy.select();
				document.execCommand("copy");
				document.body.removeChild(dummy);
				bootstrap_modal('URL Copied', url);
			} catch (err) {
			}
		});
		// Append monkey bar modals to <body> element
		if ($('.monkey-bar-modal').length) {
			$('.monkey-bar-modal').appendTo('body');
		}
		// Check cookies
		if (!cookies_enabled()) {
			var msg = 'The use of cookies seems to have been disabled in your browser. ';
			msg += 'In order to be able to use formr and have a good user experience, you will need to enable cookies.';
			bootstrap_modal('Cookies Disabled', msg);
		}
	});
}(jQuery));


if ('serviceWorker' in navigator) {
   navigator.serviceWorker.register('sw.js')
   .then(function(reg) {
      console.log('SW successfully installed');
        configurePushSub();
   }).catch(function(error) {
      console.log('Registration failed with ' + error);
   });
}

function urlBase64ToUint8Array(base64String) {
   var padding = '='.repeat((4 - base64String.length % 4) % 4);
   var base64 = (base64String + padding)
      .replace(/\-/g, '+')
      .replace(/_/g, '/');

   var rawData = window.atob(base64);
   var outputArray = new Uint8Array(rawData.length);

   for (var i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
   }
   return outputArray;
}

function configurePushSub() {
   if (!('serviceWorker' in navigator)) {
      return;
   }

   var reg;
   navigator.serviceWorker.ready
      .then(function(swreg) {
         reg = swreg;
         return swreg.pushManager.getSubscription();
      })
      .then(function(sub) {
         if (sub === null) {
            var vapidPublicKey = 'BMjkOGYKy_sFVci1vCMhshyyJEoPS_sAyCuzYs4tImm6FMsF9FZu9pDrQnCnT88gVENH8ABLP3Ci7jUNaFPk8kQ';
            console.log('vapidPublicKey: ' . vapidPublicKey);
            var convertedVapidPublicKey = urlBase64ToUint8Array(vapidPublicKey);
            console.log(convertedVapidPublicKey);
            var retval = reg.pushManager.subscribe({
               userVisibleOnly: true,
               applicationServerKey: convertedVapidPublicKey
            });
            return retval;
         }
         return sub;
      })
      .then(function(newSub) {
         pushsubscriptionobject = newSub;
         console.log(newSub);
         jsonfied_subscription = JSON.parse(JSON.stringify(newSub));
         fetch('https://www.uni-muenster.de/PsyTD/formr-emotions/pwa/subscribe', {
            method: 'POST',
            credentials: "same-origin",
            headers: {
               'Accept': 'application/json',
               'Content-Type': 'application/json'
            },
            body: JSON.stringify(
               {
               'endpoint': jsonfied_subscription.endpoint,
               'auth': jsonfied_subscription.keys.auth,
               'p256dh': jsonfied_subscription.keys.p256dh,
               'runname': window.location.href.replace(/.*\//, '').replace(/\?.*/, '')
               })
         });


         return newSub;
      })
      .then(function(res) {
            if (res.ok) {
            displayConfirmNotification();
            }
      })
      .catch(function(err) {
            console.log(err);
      });
}

$(document).ready(function() {
    if ('Notification' in window) {
       Notification.requestPermission().then(function(permission) {
         if (permission === 'denied') {
            console.log('Permission for notifications wasn\'t granted.');
            return;
         } 
         if (permission === 'default') {
            console.log('Permissions still on default.');
            return;
         }
       });
    }
});

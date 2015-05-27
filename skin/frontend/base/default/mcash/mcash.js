document.observe("dom:loaded", function() {
    loadQRCode();
    checkScannedStatus();
});

function loadQRCode() {
    var url = getUrl("qr-image-url")
    function waitForElem() {
	if ($("qr-image") !== null ){
	    clearInterval(interval);
	    new Ajax.Request(url, {
		method: "GET",
		onSuccess: function(res) {
		    var data = res.responseJSON;
		    if (data) {
			$("qr-image").src = data.url;
			$("qr-image").removeClassName("hide");
			$("loading-qr").addClassName("hide");
		    }
		}
	    });
	}
    }
    interval = setInterval(waitForElem, 2000);
}

function checkScannedStatus() {
    var url = getUrl("status-url");
    var intervalId = setInterval(fetchStatus, 2000);

    function fetchStatus() {
        new Ajax.Request(url, {
            method: "GET",
            onSuccess: function(res) {
                var data = res.responseJSON;
                if (!data) {
                    return;
                }

                if (data.scanned === true) {
                    hideQrCode();
                    showIsScanned();
                    clearInterval(intervalId);
		    mcash_ask_for_scopes = document.getElementById("mcash_ask_for_scopes").textContent
		    if (mcash_ask_for_scopes === 'yes'){
			requestPermissions();
		    }
                }
            }
        });
    }

    function hideQrCode() {
        $$(".mcash-qr").each(function(el) {
            el.addClassName("hide");
        });
    }

    function showIsScanned() {
        $("mcash-qr-scanned").removeClassName("hide");
    }
}

function requestPermissions() {
    var url = getUrl("request-permissions-url");
    new Ajax.Request(url, {
        method: "GET",
        onSuccess: function(res) {
            var data = res.responseJSON;
            if (!data) {
                return;
            }

            if (data.success) {
                getUserinfo();
            }
        }
    });
}

function getUserinfo() {
    var url = getUrl("userinfo-url");
    var intervalId = setInterval(fetch, 2000);

    function fetch() {
        new Ajax.Request(url, {
            method: "GET",
            onSuccess: function(res) {
                var data = res.responseJSON;
                if (!data) {
                    return;
                }

                if (data.ready) {
                    clearInterval(intervalId);
                    $(document).fire("mcash:userinfo", data.userinfo);
                }
            }
        });
    }
}

function getUrl(name) {
    name = "data-mcash-" + name
    return $$("[" + name + "]").first().readAttribute(name);
}

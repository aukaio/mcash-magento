document.observe("dom:loaded", function() {
    var statusUrl = $$("[data-mcash-status-url]").first().readAttribute("data-mcash-status-url")
    var intervalId = setInterval(checkQRStatus, 2000);

    function checkQRStatus() {
        new Ajax.Request(statusUrl, {
            onSuccess: function(res) {
                var data = res.responseJSON;
                if (!data) {
                    return;
                }

                if (data.scanned === true) {
                    clearInterval(intervalId);
                    hideQrCode();
                    showIsScanned();
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
});

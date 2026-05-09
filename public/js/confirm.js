$(document).ready(function () {
    window.confirmAction = function (input) {
        var url = $(input).closest("td").attr("data-url");
        $("#statutForm").prop("action", url);
    }
});



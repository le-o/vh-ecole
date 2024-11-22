$(".check-all-line").click(function() {
    var selector = $(this).is(":checked") ? ":not(:checked)" : ":checked";

    $(this).closest("tr").find("input[type='checkbox']" + selector).each(function() {
        $(this).prop("checked", !$(this).prop("checked"));
    });
});
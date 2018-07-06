var swVersions = {
    "sw-5-2": [
        {value: "sw-5-2-0", label: "5.2.0"},
        {value: "sw-5-2-1", label: "5.2.1"},
        {value: "sw-5-2-2", label: "5.2.2"},
        {value: "sw-5-2-3", label: "5.2.3"},
        {value: "sw-5-2-4", label: "5.2.4"},
        {value: "sw-5-2-5", label: "5.2.5"},
        {value: "sw-5-2-6", label: "5.2.6"},
        {value: "sw-5-2-7", label: "5.2.7"},
        {value: "sw-5-2-8", label: "5.2.8"},
        {value: "sw-5-2-9", label: "5.2.9"},
        {value: "sw-5-2-10", label: "5.2.10"},
        {value: "sw-5-2-11", label: "5.2.11"},
        {value: "sw-5-2-12", label: "5.2.12"},
        {value: "sw-5-2-13", label: "5.2.13"},
        {value: "sw-5-2-14", label: "5.2.14"},
        {value: "sw-5-2-15", label: "5.2.15"},
        {value: "sw-5-2-16", label: "5.2.16"},
        {value: "sw-5-2-17", label: "5.2.17"},
        {value: "sw-5-2-18", label: "5.2.18"},
        {value: "sw-5-2-19", label: "5.2.19"},
        {value: "sw-5-2-20", label: "5.2.20"},
        {value: "sw-5-2-21", label: "5.2.21"},
        {value: "sw-5-2-22", label: "5.2.22"},
        {value: "sw-5-2-23", label: "5.2.23"},
        {value: "sw-5-2-24", label: "5.2.24"},
        {value: "sw-5-2-25", label: "5.2.25"},
        {value: "sw-5-2-26", label: "5.2.26"},
        {value: "sw-5-2-27", label: "5.2.27"}
    ],
    "sw-5-3": [
        {value: "sw-5-3-0", label: "5.3.0"},
        {value: "sw-5-3-1", label: "5.3.1"},
        {value: "sw-5-3-2", label: "5.3.2"},
        {value: "sw-5-3-3", label: "5.3.3"},
        {value: "sw-5-3-4", label: "5.3.4"},
        {value: "sw-5-3-5", label: "5.3.5"},
        {value: "sw-5-3-6", label: "5.3.6"},
        {value: "sw-5-3-7", label: "5.3.7"}
    ],
    "sw-5-4": [
        {value: "sw-5-4-0", label: "5.4.0"},
        {value: "sw-5-4-1", label: "5.4.1"},
        {value: "sw-5-4-2", label: "5.4.2"},
        {value: "sw-5-4-3", label: "5.4.3"},
        {value: "sw-5-4-4", label: "5.4.4"},
        {value: "sw-5-4-5", label: "5.4.5"}
    ]
};

function changeContent(filename) {
    $('#ajax-container').hide().load('sw-versions/' + filename + '.html').fadeIn();
}

$(function() {
    var $filter = $("#filter");
    var $specificVersion = $("#specific-version");

    changeContent($specificVersion.val());

    $filter.jcOnPageFilter({
        animateHideNShow: true,
        focusOnLoad:false,
        highlightColor:'yellow',
        textColorForHighlights:'#000000',
        caseSensitive:false,
        hideNegatives:true,
        parentLookupClass:'jcorgFilterTextParent',
        childBlockClass:'jcorgFilterTextChild'
    });

    $specificVersion.on('change', function() {
        $filter.val('');
        $filter.trigger('keyup');
        changeContent(this.value);
        $('.current-version').html($specificVersion.find("option:selected").html());
    });

    $('#major-version').on('change', function() {
        $specificVersion.find('option').remove();
        $.each(swVersions[this.value], function (i, item) {
            $specificVersion.append($('<option>', {
                value: item.value,
                text : item.label
            }));
        });
    });
});
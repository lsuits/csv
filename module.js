M.gradeexport_csv = {};

M.gradeexport_csv.init = function(Y, indexes) {
    var cancel = function(elem, index) {
        if (indexes[index]) {
            elem.hide();
        }
    };

    Y.all('.region-content table th').each(cancel);
    Y.all('.region-content table tr').each(function(row) {
        row.all('td').each(cancel);
    });
};

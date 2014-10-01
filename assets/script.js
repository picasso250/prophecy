$.extend(DateInput.DEFAULT_OPTS, {
    stringToDate: function(string) {
        var matches;
        if (matches = string.match(/^(\d{4,4})-(\d{2,2})-(\d{2,2})/)) {
            return new Date(matches[1], matches[2] - 1, matches[3]);
        } else {
            return null;
        };
    },

    dateToString: function(date) {
        var month = (date.getMonth() + 1).toString();
        var dom = date.getDate().toString();
        if (month.length == 1) month = "0" + month;
        if (dom.length == 1) dom = "0" + dom;
        return date.getFullYear() + "-" + month + "-" + dom + ' 00:00:00';
    }
});
$($.date_input.initialize);

$(function () {
    $('.bet-button').click(function(){
        var $this = $(this);
        var id = $this.data('id');
        var is_defend = $this.data('defend');
        var points = $('#Points'+id).val();
        if (points <= 0) {
            alert('points should greater than 0');
        };
        var data = {
            id: id,
            points: points,
            is_defend: is_defend
        };
        $.post('/predict/'+id+'/attitude', data, function (ret) {
            alert(ret.message);
        }, 'json');
    });
});

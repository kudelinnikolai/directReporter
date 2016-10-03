//document.ready ();

$('#btnCreateReport').click(clickbtnCreateReport);


function clickbtnCreateReport(){

    $.ajax ({
        url : "http://api-metrika.yandex.ru/management/v1/counters?oauth_token=05dd3dd84ff948fdae2bc4fb91f13e22bb1f289ceef0037",
        type: "POST",
        data: ({"param" : {
                    "CampaignIDS": ["9166900"],
                    "StartDate": "2015-01-15",
                    "EndDate": "2015-08-18",
                    "Currency": "RUB",
                    "IncludeVAT" : "Yes"
                    },
                "method" : "GetSummaryStat",
                "token" : "6edd40b56d63400c9cd9f88666fc3645",
                "locale" : "ru"
                }),

        dataType: "jsonp",
        beforeSend: funcBeforeSend,
        success: funcSuccess,
        error: function () {
            alert("error");
        }
    })
}

function funcBeforeSend() {
    $("#btnCreateReport").html("<i class='fa fa-refresh fa-spin fa-lg fa-fw margin-bottom'></i>");
    $("#btnCreateReport").attr({disabled: "disabled"});
}

function funcSuccess(data) {
    $("#btnCreateReport").text("Формирование отчетов");
    alert(data);
    var pD = JSON.parse(data);
    alert(pD);
    $("#btnCreateReport").removeAttr("disabled");
}
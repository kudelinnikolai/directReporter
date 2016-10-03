$('#directory').focusout(clickbtnCreateReport);

function clickbtnCreateReport(){

    $.ajax ({
        url : "../ajaxJs/isSetDir.php",
        type: "POST",
        data: ({
            "directory" : $("#directory").val()
        }),
        timeout: 5000,
        dataType:"text",
        beforeSend: funcBeforeSend,
        success: funcSuccess,
        /*error: function () {
            alert("error");
        }*/
        error: function(jqXHR, exception)
        {
            if (jqXHR.status === 0) {
                alert('НЕ подключен к интернету!');
            } else if (jqXHR.status == 404) {
                alert('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                alert('НЕ найден домен в запросе [500].');
            } else if (exception === 'parsererror') {
                alert("Ошибка в коде: \n"+jqXHR.responseText);
            } else if (exception === 'timeout') {
                alert('Не ответил на запрос.');
            } else if (exception === 'abort') {
                alert('Прерван запрос Ajax.');
            } else {
                alert('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        }
    })
}

function funcBeforeSend() {}

function funcSuccess(data) {
    if(data === "Директория не найдена" && $('#directory').val()!= ""){
        $('#directory').parent().removeClass("has-success");
        $('#directory').next().remove();

        $('#directory').parent().addClass("has-error");
        $('#directory').after("<span class=\"glyphicon glyphicon-remove form-control-feedback\"></span>");
    } else if(data === "Директория обнаружена"){
        $('#directory').parent().removeClass("has-error");
        $('#directory').next().remove();

        $('#directory').parent().addClass("has-success");
        $('#directory').after("<span class=\"glyphicon glyphicon-ok form-control-feedback\"></span>");
    }
}

/*$('#signIn').click(letsDoIt);
function letsDoIt() {
    var newWin = window.open("https://oauth.yandex.ru/authorize?", "_blank");
    newWin.location.href="https://oauth.yandex.ru/authorize?response_type=token&client_id=adf2c692868d49f7abab7b9fe9ad733b";
    alert(newWin.location);

    var token = /access_token=([-0-9a-zA-Z_]+)/.exec(newWin.location.hash)[1];
    alert(token);
}*/
function getParam() {
  var params = location.href.split("?")[1].split("&");
  var paramsArray = [];
  for ( i = 0; i < params.length; i++ ) {
    neet = params[i].split("=");
    paramsArray.push(neet[0]);
    paramsArray[neet[0]] = neet[1];
  }
  return paramsArray;
}

function changeTab(tabname){
  document.getElementById('tab-yet').style.display = 'none';
  document.getElementById('tab-done').style.display = 'none';

  document.getElementById(tabname).style.display = 'block';

  if(document.getElementById('tab-yet').style.display == 'block'){
    document.getElementsByClassName('tab1')[0].style.background = '#70aac8';
    document.getElementsByClassName('tab2')[0].style.background = '#c37979';
  } else {
    document.getElementsByClassName('tab1')[0].style.background = '#387290';
    document.getElementsByClassName('tab2')[0].style.background = '#e39999';
  }
}

var ws;
window.onload = function() {
  changeTab('tab-yet');

  $.getJSON("http://"+(getParam()['apihost'] || window.location.hostname)+"/api/events/"+event_id+"/talks")
    .success(function(data) {
      data.forEach(function(t) {
        console.log(t);
        $($("#talk-tmpl").render(t)).appendTo("#tab-yet");
      });
    });

  $("#settime").on('click', function(e) {
    var sec = parseInt($("select[name=min]").val())*60 + parseInt($("select[name=sec]").val());
    ws.send(JSON.stringify({f: "timer_set", v: sec}));
  });

  $("#ppbutton").on('click', function(e) {
    var $this = $(this);
    if($this.hasClass('play')) {
      $this.html($("<i>").addClass("fa fa-pause"));
      $this.removeClass("play").addClass("pause");
      ws.send(JSON.stringify({f: "timer_start"}));
    } else {
      $this.html($("<i>").addClass("fa fa-play"));
      $this.removeClass("pause").addClass("play");
      ws.send(JSON.stringify({f: "timer_pause"}));
    }
  });

  ws = new WebSocket("ws://fmfes-subscreen-server.herokuapp.com/");
  ws.onopen = function(msg) {
    console.log(msg);
  }

  ws.onmessage = function(msg) {
    var data = JSON.parse(msg.data);
    var content = data.content;
    console.log(data);
    console.log(content);
    if (data.type === "notify") {

      // update information
      if (content.f === "update_talk") {
        var host = getParam()['apihost'] || "fmfes-herokubutton.herokuapp.com";
        $.getJSON('http://'+host+'/api/talks/' + content.v)
          .success(function(data) {
            console.log(data);
            $("#title")
              .html(data.title)
              .velocity("fadeIn", { duration: 1000 });
            $("#team")
              .html(data.team_name)
              .velocity("fadeIn", { duration: 1000 });
          });
      } else if (content.f === "info") {
        $("#info")
          .html(content.v)
          .velocity("fadeIn", { duration: 1000 });
      }

    } else if (data.type === "timer") {

      // update timer
      var min = ("00" + Math.floor(content.time / 60)).substr(-2);
      var sec = ("00" + content.time % 60).substr(-2);
      $("#time").html(min+":"+sec);
      if(min == "00" && sec == "00") {
        var $ppbutton = $("#ppbutton")
        $ppbutton.html($("<i>").addClass("fa fa-play"));
        $ppbutton.removeClass("pause").addClass("play");
      }

    }
  }

  $.getJSON()
}

<!DOCTYPE >

<html>
<head>
	<title>Untitled</title>
</head>
<script>
    osy_speach = 
    {
        reco : null,        
        init : function()
        {
            if (!('webkitSpeechRecognition' in window)) 
            {
               alert('Web speech API is not supported in this browser');
               return;
            } 
            this.reco = new webkitSpeechRecognition();
            // continously listen to speech
            this.reco.continuous = true;
            // set languages supported
            this.reco.lang = 'it'; //['Italian - Italy', ['it-IT', 'Italy']];
            this.reco.interimResults = true;
            //this.reco.onsoundend = this.reco.stop
            this.reco.onresult = osy_speach.result;
            //this.reco.completeTimeout = 1000;
            this.reco.start();
            
        },
        result : function(e){
          var voice_cmd = '';
          //osy_speach.reco.stop();
          if (e.results.length > 0) 
          {             
             console.log(e.results);
             //document.getElementById('cmd').value += event.results[0][0].transcript+'\n';
             for (var i = event.resultIndex; i < event.results.length; i++) 
             {
                 if (event.results[i].isFinal == true)
                 {
                    voice_cmd = event.results[i][0].transcript; 
                     document.getElementById('cmd').value += voice_cmd+'\n';                
                 }
             } 
          }
        }
    }
</script>
<body>

<script>
osy_speach.init();
/*var u = new SpeechSynthesisUtterance();
     u.text = 'Vai in 4 5 3 2';
     u.lang = 'it-IT';
     u.rate = 1.2;
     u.onend = function(event) { console.log('Speech complete'); }
     speechSynthesis.speak(u);*/
</script>
<form>
    <input type="text" x-webkit-speech="x-webkit-speech" />
    <textarea id="cmd" cols="100" rows="50"></textarea>
</form>
</body>
</html>

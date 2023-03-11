<html>
<head>
    <title>Commando</title>
    <link href="https://fonts.googleapis.com/css?family=PT+Mono" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-resource@1.5.1"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-scrollto"></script>
    <style>
        * {
            box-sizing: border-box;
        }
        .before {max-width: 16.66%;}
        .content {width: 75%;}
        .load {
            width: 8.33%;
            float: right;
            padding: 15px;
        }

        .before, .content {
            float: left;
            padding: 15px;
        }

        body {
            font-family: 'PT Mono', monospace;
            color: #bb51cc;
            background-color: black;
        }
        input {
            font-family: 'PT Mono', monospace;
            color: #bb51cc;
            background-color: black;
            border: none;
            width: 90%;
            height: 20px;
        }
        .prompt {
            display: -ms-flexbox;
            display: -webkit-flex;
            display: flex;

            -ms-flex-align: center;
            -webkit-align-items: center;
            -webkit-box-align: center;

            align-items: center;
            height: 30px;
        }
        .path {
            display: inline-flex;
            max-width: 80%;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
<div id="commando">
    <pre>{{output}}</pre>
    <div class="prompt">
        <span class="before">[<span class="path">{{cmdPath}}</span>] $</span>
        <input class="content" v-model="cmd" ref="cmd" id="cmd" v-on:keyup.enter='send'/>
        <span v-if="loading" class="load"><img src="loading.svg"/></span>
    </div>
</div>
<script>

    function scroll() {
        setInterval(function() {
            document.body.scrollTop = document.body.scrollHeight;
        }, 50);
    }

    var robot = "   ,--.\n" +
        "  |__**|\n" +
        "  |//  |\n" +
        "  /o|__|  [Commando Web Terminal]";

    var vue = new Vue({
        el: '#commando',
        data: {
            cmd: '',
            output: robot,
            loading: false,
            cmdPath: '',
        },
        methods: {
            send: function() {
                this.loading = true;
                this.$http.post('exec.php', {command:this.cmd}).then(function (response) {
                    this.cmdPath = response.body.path;
                    this.output += "\n";
                    $this = this;
                    if (response.body.output) {
                        response.body.output.forEach(function(data){
                            $this.output += data + "\n";
                        });
                    }
                    this.cmd = '';

                    this.loading = false;
                    document.getElementById('cmd').focus();
                    scroll();
                })
            },
            init: function() {
                document.getElementById('cmd').focus();
            },
        },
        mounted() {
            this.init();
            this.send();
        }
    });
</script>
</body>
</html>

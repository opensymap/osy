<?php
class terminal
{
    private $color = array();
    private $keyboard = null;
    
    public function __construct()
    {
        $this->color['green']  = "[42m"; //Green background
        $this->color['red']    = "[41m"; //Red background
        $this->color['yellow'] = "[43m"; //Yellow
        $this->color['blue']   = "[44m"; //Blue
        $this->keyboard = fopen("php://stdin","r");
    }
    
    public function label($text,$color='blue')
    {
        return chr(27) . $this->color[$color] . "$text" . chr(27) . "[0m";
    }
    
    public function input($label,$color='blue')
    {
        print $this->label($label,$color);
        $resp = fgets($this->keyboard,80);
        return $resp;
    }
}


//echo colorize("Your command was successfully executed...", "SUCCESS");

# Calculation of Body - Mass Index - with user inputs!

# Open "stdin" for read - to read from keyboard [default connection for stdin]
# (most languages do this automatically but then they are NOT web based!)

$terminal = new terminal();
$altezza = $terminal->input("How tall are you in metres? ");
$peso  = $terminal->input("How heavy are you in kgs? ");

$bmi = $peso / $altezza / $altezza;

# could have written
# $bmi = $weigh / ($tall * $tall);

# Brackets change the order of operations
# default: * / and % left to right first, then + and - left to right
print $terminal->label($bmi);
print PHP_EOL;

# We will deal with absurd overaccuracy later


var loadPlayerDice = function() {
    var p1CurrentHealth = 20;
    var p2CurrentHealth = 20;
    
    //filenames for health dice
    var playerOneHealth = ['pone0.png','pone1.png','pone2.png','pone3.png','pone4.png','pone5.png','pone6.png','pone7.png','pone8.png','pone9.png','pone10.png','pone11.png','pone12.png','pone13.png','pone14.png','pone15.png','pone16.png','pone17.png','pone18.png','pone19.png','pone20.png'];
    var playerTwoHealth = ['ptwo0.png','ptwo1.png','ptwo2.png','ptwo3.png','ptwo4.png','ptwo5.png','ptwo6.png','ptwo7.png','ptwo8.png','ptwo9.png','ptwo10.png','ptwo11.png','ptwo12.png','ptwo13.png','ptwo14.png','ptwo15.png','ptwo16.png','ptwo17.png','ptwo18.png','ptwo19.png','ptwo20.png'];
    
    //actual image tags
    var p1Image = document.getElementById('player1health');
    var p2Image = document.getElementById('player2health');
    
    //buttons to adjust health
    var p1DecreaseButton = document.getElementById('player1down');
    var p2DecreaseButton = document.getElementById('player2down');
    var p1Decrease3Button = document.getElementById('player1down3');
    var p2Decrease3Button = document.getElementById('player2down3');
    var p1IncreaseButton = document.getElementById('player1up');
    var p2IncreaseButton = document.getElementById('player2up');
    var p1Increase3Button = document.getElementById('player1up3');
    var p2Increase3Button = document.getElementById('player2up3');
    var roll = document.getElementById('roll');
    var reset = document.getElementById('reset');
    
    function spinPlayerHealth(p) {
        if(p === 1) {
            p1Image.src = "images/" + playerOneHealth[p1CurrentHealth];
            p1Image.alt = p1CurrentHealth + " Health";
            p1Image.toggleClassName('spun');
        } else {
            p2Image.src = "images/" + playerTwoHealth[p2CurrentHealth];
            p2Image.alt = p2CurrentHealth + " Health";
            p2Image.toggleClassName('spun');
        }
    }
    
    //functions for event clicks
    p1DecreaseButton.addEventListener('click', function() {
        if(p1CurrentHealth > 0) {
            --p1CurrentHealth;
            spinPlayerHealth(1);
        }
    });
    p2DecreaseButton.addEventListener('click', function() {
        if(p2CurrentHealth > 0) {
            --p2CurrentHealth;
            spinPlayerHealth(2);
        }
    });
    p1Decrease3Button.addEventListener('click', function() {
        if(p1CurrentHealth > 0) {
            p1CurrentHealth -= 3;
            if(p1CurrentHealth < 0) {
                p1CurrentHealth = 0;
            }
            spinPlayerHealth(1);
        }
    });
    p2Decrease3Button.addEventListener('click', function() {
        if(p2CurrentHealth > 0) {
            p2CurrentHealth -= 3;
            if(p2CurrentHealth < 0) {
                p2CurrentHealth = 0;
            }
            spinPlayerHealth(2);
        }
    });
    p1IncreaseButton.addEventListener('click', function() {
        ++p1CurrentHealth;
        spinPlayerHealth(1);
    });
    p2IncreaseButton.addEventListener('click', function() {
        ++p2CurrentHealth;
        spinPlayerHealth(2);
    });
    p1Increase3Button.addEventListener('click', function() {
        p1CurrentHealth += 3;
        spinPlayerHealth(1);
    });
    p2Increase3Button.addEventListener('click', function() {
        p2CurrentHealth += 3;
        spinPlayerHealth(2);
    });
    roll.addEventListener('click', function() {
        //random number between 20 and 1
        p1CurrentHealth =  Math.floor(Math.random() * (20 - 1 + 1)) + 1;
        p2CurrentHealth = Math.floor(Math.random() * (20 - 1 + 1)) + 1;
        
        spinPlayerHealth(1);
        spinPlayerHealth(2);
    });
    reset.addEventListener('click', function() {
        //reset health
        p1CurrentHealth = 20;
        p2CurrentHealth = 20;
        
        spinPlayerHealth(1);
        spinPlayerHealth(2);
    });
    
    p1Image.addEventListener( 'transitionend', function() { p1Image.removeClassName('spun'); });
    p2Image.addEventListener( 'transitionend', function() { p2Image.removeClassName('spun'); });
    p1Image.addEventListener( 'webkitTransitionEnd', function() { p1Image.removeClassName('spun'); });
    p2Image.addEventListener( 'webkitTransitionEnd', function() { p2Image.removeClassName('spun'); });
};

window.addEventListener('DOMContentLoaded', loadPlayerDice, false);

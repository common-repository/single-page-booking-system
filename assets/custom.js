document.addEventListener("DOMContentLoaded", function(){
    //AUTOHEIGHT
    setAutoHeight();

    //Toggle on off view password
    let user_pass = document.getElementById('user_pass');
    let pass1 = document.getElementById('pass1');
    let inputUsername = document.getElementById('user_login')
    let togglePassword = document.getElementById('toggle-password');
    let inputPassword = '';
    let passStrength = document.getElementById('pass-strength-result');
    let passmark = document.getElementById('passmark');
    let strenghtBox = document.getElementById('pass-strenght');
    let autoPassBtn = document.getElementById('generate-pass');
    let wpSubmit = document.getElementById('wp-submit');
    //disable buton save
    if(wpSubmit){
        wpSubmit.disabled = true;
        if(document.getElementById('lostpasswordform')){
            wpSubmit.disabled = false;
        }
    }
    //autogenerate password
    if(autoPassBtn){
        let password='';
        autoPassBtn.addEventListener('click', function(){
            password=autoGeneratePassword();
            inputPassword.value=password;
            pass1.setAttribute('data-pw', inputPassword.value);
            passStrength.textContent = 'Strong';            
            wpSubmit.disabled = false;
        });
    }

    //pasar atributos del value al data password
    if(pass1){
        pass1.addEventListener('input', function(){
            pass1.setAttribute('data-pw', inputPassword.value);
            if(pass1.value.length>7){
                strenghtBox.style.visibility = 'visible';
            }else{
                strenghtBox.style.visibility = 'hidden';
            }
        });
    }
    
    //observer para mostrar el boton check de weak password
    let observer = new MutationObserver(function(mutations){
        mutations.forEach(function(mutation){
            if(passStrength && passmark){
                if(passStrength.textContent.includes('eak')&&pass1.value.length>7){
                    passmark.style.display = 'block';
                    pwWeak.disabled=false;
                    strenghtBox.getElementsByTagName('label')[0].style.cursor = 'pointer';
                }else{
                    passmark.style.display = 'none';
                    pwWeak.disabled=true;
                    strenghtBox.getElementsByTagName('label')[0].style.cursor = 'default';
                }    
            }
        });
    });
    let config = {attibutes: true, childList: true, CharacterData: true};
    if(passStrength){observer.observe(passStrength,config);}

    //checkbox
    let pwWeak = document.getElementById('pw-weak');
    if(pwWeak){
        pwWeak.addEventListener('change', function(){
            if(this.checked){
                wpSubmit.disabled = false;
            }else{
                wpSubmit.disabled = true;
            }
        });
    }

    user_pass?inputPassword=user_pass:pass1?inputPassword=pass1:'';
    
    //toggle
    if(togglePassword != null && inputPassword != null){
        togglePassword.addEventListener('click', function(e){
        if(inputPassword.type == 'text'){
            inputPassword.type = 'password';
            if(togglePassword.classList.contains('fa-eye-slash')){
                togglePassword.classList.remove('fa-eye-slash');
                togglePassword.classList.add('fa-eye');
            }
        }else if(inputPassword.type == 'password'){
            inputPassword.type = 'text';
            if(togglePassword.classList.contains('fa-eye')){
                togglePassword.classList.remove('fa-eye');
                togglePassword.classList.add('fa-eye-slash');
            }
        }
        });
    }
    //Clear inputs
    inputPassword?inputPassword.value='':'';
    inputUsername?inputUsername.value='':'';
    inputUsername?inputUsername.focus():'';
});

//autoheight
function setAutoHeight(){    
    let jsAutoHeight = document.getElementsByClassName('js-autoheight');
    let winHeight = window.innerHeight;    
    for(let i=0; i<jsAutoHeight.length; i++){
        jsAutoHeight[i].style.height = winHeight + 'px';
    }
}
window.addEventListener('resize',setAutoHeight);

function autoGeneratePassword(){
    let passChar = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+';
    let passLength = 16;
    let password = '';
    for(let i=0;i<passLength;i++){
        password += passChar.charAt(Math.floor(Math.random()*passChar.length));
    }
    return password;
}
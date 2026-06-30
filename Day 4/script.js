
  // ---- Dark mode toggle ----
  const themeToggle = document.getElementById('themeToggle');
  const body = document.body;
  themeToggle.addEventListener('click', () => {
    const isDark = body.getAttribute('data-theme') === 'dark';
    body.setAttribute('data-theme', isDark ? 'light' : 'dark');
    themeToggle.textContent = isDark ? '🌙 Dark mode' : '☀️ Light mode';
  });

  // ---- Click counter with reset ----
  let count = 0;
  const countVal = document.getElementById('countVal');
  document.getElementById('countBtn').addEventListener('click', () => {
    count++;
    countVal.textContent = count;
  });
  document.getElementById('resetBtn').addEventListener('click', () => {
    count = 0;
    countVal.textContent = count;
  });

  // ---- Form validation ----
  const form = document.getElementById('regForm');
  const nameInput = document.getElementById('name');
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const confirmInput = document.getElementById('confirm');
  const successBanner = document.getElementById('successBanner');

  function setError(input, errorEl, message){
    if(message){
      input.classList.add('invalid');
      errorEl.textContent = message;
      return false;
    } else {
      input.classList.remove('invalid');
      errorEl.textContent = '';
      return true;
    }
  }

  function validateName(){
    const v = nameInput.value.trim();
    return setError(nameInput, document.getElementById('nameError'),
      v.length === 0 ? 'Name cannot be empty.' :
      v.length < 2 ? 'Name is too short.' : '');
  }

  function validateEmail(){
    const v = emailInput.value.trim();
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return setError(emailInput, document.getElementById('emailError'),
      v.length === 0 ? 'Email cannot be empty.' :
      !re.test(v) ? 'Enter a valid email address.' : '');
  }

  function validatePassword(){
    const v = passwordInput.value;
    return setError(passwordInput, document.getElementById('passwordError'),
      v.length === 0 ? 'Password cannot be empty.' :
      v.length < 8 ? 'Password must be at least 8 characters.' : '');
  }

  function validateConfirm(){
    const v = confirmInput.value;
    return setError(confirmInput, document.getElementById('confirmError'),
      v.length === 0 ? 'Please confirm your password.' :
      v !== passwordInput.value ? 'Passwords do not match.' : '');
  }

  // live validation as user types/leaves field
  nameInput.addEventListener('blur', validateName);
  emailInput.addEventListener('blur', validateEmail);
  passwordInput.addEventListener('blur', validatePassword);
  confirmInput.addEventListener('blur', validateConfirm);
  passwordInput.addEventListener('input', () => { if(confirmInput.value) validateConfirm(); });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    successBanner.classList.remove('show');
    const validations = [validateName(), validateEmail(), validatePassword(), validateConfirm()];
    if(validations.every(Boolean)){
      successBanner.classList.add('show');
      form.reset();
      [nameInput, emailInput, passwordInput, confirmInput].forEach(i => i.classList.remove('invalid'));
    }
  });
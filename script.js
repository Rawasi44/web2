// Toggle learner/educator forms
const radios = document.querySelectorAll('input[name="uType"]');
const learnerForm = document.getElementById('learnerForm');
const educatorForm = document.getElementById('educatorForm');

radios.forEach(r => r.addEventListener('change', (e) => {
  const v = e.target.value;
  learnerForm.style.display = (v === 'learner') ? 'block' : 'none';
  educatorForm.style.display = (v === 'educator') ? 'block' : 'none';
}));

// Require at least one topic for educator before submitting

const educatorTopics = educatorForm.querySelectorAll('input[name="topic[]"]');

educatorForm.addEventListener('submit', (e) => {
  const oneChecked = Array.from(educatorTopics).some(cb => cb.checked);
  if (!oneChecked) {
    e.preventDefault();
    alert('Please select at least one topic before submitting.');
  }
});
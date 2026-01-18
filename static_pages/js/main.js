document.addEventListener('DOMContentLoaded', function() {
    console.log('InternEasy frontend loaded');
    
    const userType = localStorage.getItem('userType') || 'student';
    
    if (document.getElementById('btnStudent')) {
        document.getElementById('btnStudent').addEventListener('click', function() {
            setUserType('student');
        });
        
        document.getElementById('btnCompany').addEventListener('click', function() {
            setUserType('company');
        });
    }
    
    function setUserType(type) {
        localStorage.setItem('userType', type);
        document.querySelectorAll('.user-type-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        if (type === 'student') {
            document.getElementById('btnStudent').classList.add('active');
        } else {
            document.getElementById('btnCompany').classList.add('active');
        }
    }
});
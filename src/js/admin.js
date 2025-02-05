function checkAdminSession() {
    const Session = localStorage.getItem('admin_session');
    
    if (Session === 'authenticated') {
        window.location.href = '/admin.php';
    }
}
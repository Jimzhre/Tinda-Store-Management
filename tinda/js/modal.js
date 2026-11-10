// ==========================================
// TINDA — Custom Delete Modal
// ==========================================

function openModal(productId) {
    const modal = document.getElementById('delete-modal');
    const confirmBtn = document.getElementById('confirm-delete-btn');
    
    // This tells the button which product to delete
    confirmBtn.href = `products.php?delete=${productId}`;
    
    // Show the modal
    modal.classList.add('show');
}

function closeModal() {
    const modal = document.getElementById('delete-modal');
    modal.classList.remove('show');
}

// Close modal if user clicks outside the white card
window.addEventListener('click', function(event) {
    const modal = document.getElementById('delete-modal');
    if (event.target === modal) {
        closeModal();
    }
});
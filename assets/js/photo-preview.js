// Script pour la prévisualisation de la photo de profil
document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('photo');
    const previewDiv = document.getElementById('photo-preview');
    const previewImage = document.getElementById('preview-image');
    const previewSection = document.getElementById('photo-preview-section');
    const previewImageNew = document.getElementById('preview-image-new');
    const photoDisplaySection = document.getElementById('photo-display-section');
    const deletePhotoBtn = document.getElementById('delete-photo-btn');
    
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Si une photo existe déjà, afficher la prévisualisation dans l'encart existant
                    if (photoDisplaySection) {
                        previewImage.src = e.target.result;
                        previewDiv.style.display = 'block';
                    } else {
                        // Si aucune photo n'existe, afficher l'encart de prévisualisation
                        previewImageNew.src = e.target.result;
                        previewSection.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            } else {
                // Masquer les prévisualisations
                if (previewDiv) {
                    previewDiv.style.display = 'none';
                }
                if (previewSection) {
                    previewSection.style.display = 'none';
                }
            }
        });
    }
    
    // Gestion de la suppression de la photo
    if (deletePhotoBtn) {
        deletePhotoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?')) {
                // Créer un champ caché pour indiquer la suppression
                let hiddenInput = document.getElementById('delete-photo');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = 'delete-photo';
                    hiddenInput.name = 'delete_photo';
                    hiddenInput.value = '1';
                    document.querySelector('form').appendChild(hiddenInput);
                }
                
                // Masquer complètement l'encart de la photo
                if (photoDisplaySection) {
                    photoDisplaySection.style.display = 'none';
                }
                
                // Réinitialiser l'input file
                if (photoInput) {
                    photoInput.value = '';
                }
                
                // Masquer la prévisualisation si elle était visible
                if (previewDiv) {
                    previewDiv.style.display = 'none';
                }
                if (previewSection) {
                    previewSection.style.display = 'none';
                }
            }
        });
    }
}); 
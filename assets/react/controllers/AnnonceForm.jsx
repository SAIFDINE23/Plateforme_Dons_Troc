import React, { useState, useEffect } from 'react';
import toast, { Toaster } from 'react-hot-toast';

export default function AnnonceForm() {
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [customCategory, setCustomCategory] = useState('');
    const [campuses, setCampuses] = useState([]);
    const [type, setType] = useState('DON');
    const [files, setFiles] = useState([]);
    const [previews, setPreviews] = useState([]);
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [currentImageIndex, setCurrentImageIndex] = useState(0);
    
    // État pour la modale d'aide Markdown
    const [showMarkdownHelp, setShowMarkdownHelp] = useState(false);

    const MAX_FILES = 6;
    const MAX_FILE_SIZE = 1048576; // 1 Mo en octets
    const MAX_DESCRIPTION_LENGTH = 2000;
    
    // Liste des campus disponibles
    const availableCampuses = [
        { value: 'CALAIS', label: '📍 Calais' },
        { value: 'DUNKERQUE', label: '📍 Dunkerque' },
        { value: 'BOULOGNE', label: '📍 Boulogne-sur-Mer' },
        { value: 'SAINT_OMER', label: '📍 Saint-Omer' }
    ];

    // Charger les catégories au montage du composant
    useEffect(() => {
        const fetchCategories = async () => {
            try {
                const response = await fetch('/api/categories');
                if (response.ok) {
                    const data = await response.json();
                    setCategories(data);
                } else {
                    console.error('Erreur lors du chargement des catégories');
                    setCategories([
                        { id: 9, name: 'Livres' },
                        { id: 10, name: 'Matériel Informatique' },
                        { id: 11, name: 'Mobilier' },
                        { id: 12, name: 'Vêtements' },
                        { id: 13, name: 'Électroménager' },
                        { id: 14, name: 'Vaisselle' },
                        { id: 15, name: 'Fournitures Scolaires' },
                        { id: 16, name: 'Sport' }
                    ]);
                }
            } catch (error) {
                console.error('Erreur réseau:', error);
                setCategories([
                    { id: 9, name: 'Livres' },
                    { id: 10, name: 'Matériel Informatique' },
                    { id: 11, name: 'Mobilier' },
                    { id: 12, name: 'Vêtements' },
                    { id: 13, name: 'Électroménager' },
                    { id: 14, name: 'Vaisselle' },
                    { id: 15, name: 'Fournitures Scolaires' },
                    { id: 16, name: 'Sport' }
                ]);
            }
        };
        
        fetchCategories();
    }, []);

    // Gestion de la sélection d'images (multi-upload)
    const handleFileChange = (e) => {
        const selectedFiles = Array.from(e.target.files);
        
        if (selectedFiles.length === 0) {
            return;
        }

        // Vérifier le nombre total d'images (actuelles + nouvelles)
        const totalFiles = files.length + selectedFiles.length;
        if (totalFiles > MAX_FILES) {
            toast.error(`Vous ne pouvez pas ajouter plus de ${MAX_FILES} images. Actuellement : ${files.length}/${MAX_FILES}`);
            e.target.value = ''; // Réinitialiser l'input
            return;
        }

        // Valider chaque fichier
        const validFiles = [];
        const newPreviews = [];

        for (const file of selectedFiles) {
            // Vérification de la taille (1 Mo max)
            if (file.size > MAX_FILE_SIZE) {
                toast.error(`${file.name} dépasse 1 Mo (${(file.size / 1024 / 1024).toFixed(2)} Mo). Fichier rejeté.`);
                continue;
            }

            // Vérification du type
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                toast.error(`${file.name} : Format non accepté. Utilisez JPG, PNG ou WEBP.`);
                continue;
            }

            validFiles.push(file);

            // Créer l'aperçu
            const reader = new FileReader();
            reader.onloadend = () => {
                newPreviews.push(reader.result);
                if (newPreviews.length === validFiles.length) {
                    setPreviews([...previews, ...newPreviews]);
                }
            };
            reader.readAsDataURL(file);
        }

        if (validFiles.length > 0) {
            setFiles([...files, ...validFiles]);
            setError('');
            toast.success(`${validFiles.length} image(s) ajoutée(s) avec succès`);
        }

        e.target.value = ''; // Réinitialiser l'input pour permettre de sélectionner à nouveau
    };

    // Supprimer une image
    const removeImage = (index) => {
        const newFiles = files.filter((_, i) => i !== index);
        const newPreviews = previews.filter((_, i) => i !== index);
        setFiles(newFiles);
        setPreviews(newPreviews);
        toast.success('Image supprimée');
    };

    // Gestion de la sélection des campus
    const handleCampusToggle = (campusValue) => {
        if (campuses.includes(campusValue)) {
            setCampuses(campuses.filter(c => c !== campusValue));
        } else {
            setCampuses([...campuses, campusValue]);
        }
    };

    // Soumission du formulaire
    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');

        // Validation côté client
        if (!title || !description || !categoryId || campuses.length === 0) {
            setError('Tous les champs sont obligatoires');
            return;
        }

        if (description.length > MAX_DESCRIPTION_LENGTH) {
            setError(`La description ne peut pas dépasser ${MAX_DESCRIPTION_LENGTH} caractères`);
            return;
        }

        if (files.length === 0) {
            setError('Au moins une image est obligatoire');
            return;
        }

        if (files.length > MAX_FILES) {
            setError(`Vous ne pouvez pas uploader plus de ${MAX_FILES} images`);
            return;
        }

        setLoading(true);
        const toastId = toast.loading('Envoi en cours...');

        // Création du FormData
        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', description);
        formData.append('categoryId', categoryId);
        if (customCategory) {
            formData.append('customCategory', customCategory);
        }
        // Envoyer les campus comme tableau JSON
        formData.append('campuses', JSON.stringify(campuses));
        formData.append('type', type);
        
        // Ajouter toutes les images
        files.forEach((file, index) => {
            formData.append(`images[]`, file);
        });

        try {
            const response = await fetch('/api/annonces/new', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Erreur lors de la création de l\'annonce');
            }

            setSuccess(data.message);
            toast.success('Annonce envoyée ! En attente de validation.', { id: toastId });
            
            // Redirection après 2 secondes
            setTimeout(() => {
                window.location.href = '/home';
            }, 2000);

        } catch (err) {
            setError(err.message);
            toast.error(err.message, { id: toastId });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="container py-5">
            <Toaster position="top-right" />
            <div className="row justify-content-center">
                <div className="col-md-8 col-lg-6">
                    <div className="card shadow-sm border-0">
                        <div className="card-header bg-primary text-white">
                            <h3 className="mb-0">
                                <i className="bi bi-plus-circle me-2"></i>
                                Déposer une annonce
                            </h3>
                        </div>
                        <div className="card-body p-4">
                            {error && (
                                <div className="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i className="bi bi-exclamation-triangle me-2"></i>
                                    {error}
                                    <button type="button" className="btn-close" onClick={() => setError('')}></button>
                                </div>
                            )}

                            {success && (
                                <div className="alert alert-success" role="alert">
                                    <i className="bi bi-check-circle me-2"></i>
                                    {success}
                                </div>
                            )}

                            <form onSubmit={handleSubmit}>
                                {/* Titre */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold">
                                        Titre de l'annonce <span className="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={title}
                                        onChange={(e) => setTitle(e.target.value)}
                                        placeholder="Ex: Livre de mathématiques L1"
                                        maxLength="255"
                                        required
                                    />
                                </div>

                                {/* Description avec aide Markdown */}
                                <div className="mb-3">
                                    <div className="d-flex align-items-center justify-content-between mb-2">
                                        <label className="form-label fw-bold mb-0">
                                            Description <span className="text-danger">*</span>
                                        </label>
                                        <button
                                            type="button"
                                            className="btn btn-sm btn-outline-secondary"
                                            onClick={() => setShowMarkdownHelp(true)}
                                            title="Aide Markdown"
                                        >
                                            <i className="bi bi-question-circle me-1"></i>
                                            Aide Markdown
                                        </button>
                                    </div>
                                    <textarea
                                        className="form-control"
                                        rows="6"
                                        value={description}
                                        onChange={(e) => setDescription(e.target.value)}
                                        placeholder="Décrivez votre objet en détail... (Markdown supporté)"
                                        maxLength={MAX_DESCRIPTION_LENGTH}
                                        required
                                    ></textarea>
                                    <div className="form-text d-flex justify-content-between">
                                        <span>
                                            <i className="bi bi-markdown me-1"></i>
                                            Markdown supporté (gras, italique, listes...)
                                        </span>
                                        <span className={description.length > MAX_DESCRIPTION_LENGTH * 0.9 ? 'text-warning fw-bold' : ''}>
                                            {description.length} / {MAX_DESCRIPTION_LENGTH} caractères
                                        </span>
                                    </div>
                                </div>

                                {/* Catégorie */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold">
                                        Catégorie <span className="text-danger">*</span>
                                    </label>
                                    <select
                                        className="form-select"
                                        value={categoryId}
                                        onChange={(e) => {
                                            setCategoryId(e.target.value);
                                            if (e.target.value !== 'other') {
                                                setCustomCategory('');
                                            }
                                        }}
                                        required
                                    >
                                        <option value="">Choisir une catégorie...</option>
                                        {categories.map((cat) => (
                                            <option key={cat.id} value={cat.id}>
                                                {cat.name}
                                            </option>
                                        ))}
                                        <option value="other">🔍 Autre (préciser)</option>
                                    </select>
                                </div>

                                {/* Catégorie personnalisée */}
                                {categoryId === 'other' && (
                                    <div className="mb-3">
                                        <label className="form-label fw-bold">
                                            Préciser votre catégorie <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={customCategory}
                                            onChange={(e) => setCustomCategory(e.target.value)}
                                            placeholder="Ex: Jeux vidéo, Instruments de musique, etc."
                                            maxLength="100"
                                        />
                                        <small className="text-muted d-block mt-1">Cette catégorie sera validée par nos modérateurs</small>
                                    </div>
                                )}

                                {/* Campus - Multi-sélection avec checkboxes */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold">
                                        Campus <span className="text-danger">*</span>
                                    </label>
                                    <div className="border rounded p-3 bg-light">
                                        <small className="text-muted d-block mb-2">
                                            Sélectionnez un ou plusieurs campus où vous proposez cet objet
                                        </small>
                                        {availableCampuses.map((campus) => (
                                            <div key={campus.value} className="form-check mb-2">
                                                <input
                                                    className="form-check-input"
                                                    type="checkbox"
                                                    id={`campus-${campus.value}`}
                                                    checked={campuses.includes(campus.value)}
                                                    onChange={() => handleCampusToggle(campus.value)}
                                                />
                                                <label className="form-check-label" htmlFor={`campus-${campus.value}`}>
                                                    {campus.label}
                                                </label>
                                            </div>
                                        ))}
                                        {campuses.length === 0 && (
                                            <small className="text-danger d-block mt-2">
                                                ⚠️ Veuillez sélectionner au moins un campus
                                            </small>
                                        )}
                                    </div>
                                </div>

                                {/* Type (Don/Troc) */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold">
                                        Type d'annonce <span className="text-danger">*</span>
                                    </label>
                                    <div className="d-flex gap-3">
                                        <div className="form-check">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="type"
                                                id="typeDon"
                                                value="DON"
                                                checked={type === 'DON'}
                                                onChange={(e) => setType(e.target.value)}
                                            />
                                            <label className="form-check-label" htmlFor="typeDon">
                                                🎁 Don (gratuit)
                                            </label>
                                        </div>
                                        <div className="form-check">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="type"
                                                id="typeTroc"
                                                value="TROC"
                                                checked={type === 'TROC'}
                                                onChange={(e) => setType(e.target.value)}
                                            />
                                            <label className="form-check-label" htmlFor="typeTroc">
                                                🔄 Troc (échange)
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                {/* Images (Multi-upload) */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold">
                                        Photos de l'objet <span className="text-danger">*</span>
                                    </label>
                                    <input
                                        type="file"
                                        className="form-control"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handleFileChange}
                                        multiple
                                        disabled={files.length >= MAX_FILES}
                                    />
                                    <small className="text-muted d-block mt-1">
                                        <i className="bi bi-info-circle me-1"></i>
                                        Formats : JPG, PNG, WEBP • Max 1 Mo par image • {files.length}/{MAX_FILES} images
                                    </small>

                                    {/* Carrousel des images */}
                                    {previews.length > 0 && (
                                        <div className="mt-4">
                                            <p className="fw-bold mb-3">Aperçu des images ({previews.length}/{MAX_FILES}) :</p>
                                            
                                            {/* Carrousel principal */}
                                            <div className="position-relative mb-3" style={{
                                                backgroundColor: '#f0f0f0',
                                                borderRadius: '8px',
                                                overflow: 'hidden',
                                                minHeight: '300px',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center'
                                            }}>
                                                <img
                                                    src={previews[currentImageIndex]}
                                                    alt={`Image ${currentImageIndex + 1}`}
                                                    className="img-fluid"
                                                    style={{ maxHeight: '400px', objectFit: 'contain' }}
                                                />
                                                
                                                {/* Boutons navigation */}
                                                {previews.length > 1 && (
                                                    <>
                                                        <button
                                                            type="button"
                                                            className="btn btn-light position-absolute start-0 top-50 translate-middle-y ms-2"
                                                            onClick={() => setCurrentImageIndex((currentImageIndex - 1 + previews.length) % previews.length)}
                                                            style={{ width: '40px', height: '40px' }}
                                                        >
                                                            <i className="bi bi-chevron-left"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="btn btn-light position-absolute end-0 top-50 translate-middle-y me-2"
                                                            onClick={() => setCurrentImageIndex((currentImageIndex + 1) % previews.length)}
                                                            style={{ width: '40px', height: '40px' }}
                                                        >
                                                            <i className="bi bi-chevron-right"></i>
                                                        </button>
                                                    </>
                                                )}
                                                
                                                {/* Badges image */}
                                                <div className="position-absolute bottom-0 start-50 translate-middle-x mb-2">
                                                    <span className="badge bg-dark">{currentImageIndex + 1} / {previews.length}</span>
                                                </div>
                                            </div>
                                            
                                            {/* Miniatures */}
                                            <div className="d-flex gap-2 overflow-auto pb-2">
                                                {previews.map((preview, index) => (
                                                    <div key={index} className="position-relative flex-shrink-0">
                                                        <img
                                                            src={preview}
                                                            alt={`Miniature ${index + 1}`}
                                                            className="rounded border-2 cursor-pointer"
                                                            style={{
                                                                width: '80px',
                                                                height: '80px',
                                                                objectFit: 'cover',
                                                                borderColor: currentImageIndex === index ? '#0d6efd' : '#ccc',
                                                                borderWidth: '2px',
                                                                cursor: 'pointer',
                                                                opacity: currentImageIndex === index ? 1 : 0.7
                                                            }}
                                                            onClick={() => setCurrentImageIndex(index)}
                                                        />
                                                        <button
                                                            type="button"
                                                            className="btn btn-danger btn-sm position-absolute top-0 end-0 m-1"
                                                            onClick={() => removeImage(index)}
                                                            style={{ opacity: 0.9, width: '24px', height: '24px', padding: '0' }}
                                                        >
                                                            <i className="bi bi-x-lg" style={{ fontSize: '12px' }}></i>
                                                        </button>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Boutons */}
                                <div className="d-grid gap-2">
                                    <button
                                        type="submit"
                                        className="btn btn-primary btn-lg"
                                        disabled={loading}
                                    >
                                        {loading ? (
                                            <>
                                                <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                                Envoi en cours...
                                            </>
                                        ) : (
                                            <>
                                                <i className="bi bi-send me-2"></i>
                                                Publier l'annonce
                                            </>
                                        )}
                                    </button>
                                    <a href="/home" className="btn btn-outline-secondary">
                                        <i className="bi bi-arrow-left me-2"></i>
                                        Annuler
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {/* Modale d'aide Markdown */}
            {showMarkdownHelp && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }} tabIndex="-1">
                    <div className="modal-dialog modal-dialog-centered modal-lg">
                        <div className="modal-content">
                            <div className="modal-header bg-info text-white">
                                <h5 className="modal-title">
                                    <i className="bi bi-markdown me-2"></i>
                                    Aide-mémoire Markdown
                                </h5>
                                <button 
                                    type="button" 
                                    className="btn-close btn-close-white" 
                                    onClick={() => setShowMarkdownHelp(false)}
                                ></button>
                            </div>
                            <div className="modal-body">
                                <div className="table-responsive">
                                    <table className="table table-bordered">
                                        <thead className="table-light">
                                            <tr>
                                                <th style={{ width: '30%' }}>Syntaxe</th>
                                                <th style={{ width: '35%' }}>Exemple</th>
                                                <th style={{ width: '35%' }}>Résultat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>**texte**</code></td>
                                                <td><code>**Gras**</code></td>
                                                <td><strong>Gras</strong></td>
                                            </tr>
                                            <tr>
                                                <td><code>*texte*</code></td>
                                                <td><code>*Italique*</code></td>
                                                <td><em>Italique</em></td>
                                            </tr>
                                            <tr>
                                                <td><code>## Titre</code></td>
                                                <td><code>## Titre 2</code></td>
                                                <td><h5 className="mb-0">Titre 2</h5></td>
                                            </tr>
                                            <tr>
                                                <td><code>- Item</code></td>
                                                <td>
                                                    <code>- Premier<br/>- Deuxième</code>
                                                </td>
                                                <td>
                                                    <ul className="mb-0">
                                                        <li>Premier</li>
                                                        <li>Deuxième</li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><code>1. Item</code></td>
                                                <td>
                                                    <code>1. Premier<br/>2. Deuxième</code>
                                                </td>
                                                <td>
                                                    <ol className="mb-0">
                                                        <li>Premier</li>
                                                        <li>Deuxième</li>
                                                    </ol>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><code>[Lien](url)</code></td>
                                                <td><code>[Google](google.com)</code></td>
                                                <td><a href="#">Google</a></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div className="alert alert-info mb-0">
                                    <i className="bi bi-lightbulb me-2"></i>
                                    <strong>Astuce :</strong> Utilisez Markdown pour structurer et embellir votre description !
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button 
                                    type="button" 
                                    className="btn btn-secondary" 
                                    onClick={() => setShowMarkdownHelp(false)}
                                >
                                    Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

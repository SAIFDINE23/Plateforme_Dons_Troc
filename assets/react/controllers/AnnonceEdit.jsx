import React, { useState, useEffect } from 'react';
import toast, { Toaster } from 'react-hot-toast';

export default function AnnonceEdit({ id }) {
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        categoryId: '',
        campus: '',
        type: 'DON'
    });
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [currentImage, setCurrentImage] = useState(null);
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(false);
    const [loadingData, setLoadingData] = useState(true);
    const [error, setError] = useState('');

    // Charger les catégories
    useEffect(() => {
        setCategories([
            { id: 1, name: 'Livres' },
            { id: 2, name: 'Matériel Informatique' },
            { id: 3, name: 'Mobilier' },
            { id: 4, name: 'Vêtements' },
            { id: 5, name: 'Électroménager' },
            { id: 6, name: 'Vaisselle' },
            { id: 7, name: 'Fournitures Scolaires' },
            { id: 8, name: 'Sport' }
        ]);
    }, []);

    // Charger les données de l'annonce
    useEffect(() => {
        const fetchAnnonce = async () => {
            try {
                const response = await fetch(`/api/annonces/${id}`);
                
                if (!response.ok) {
                    throw new Error('Impossible de charger l\'annonce');
                }

                const data = await response.json();

                // Pré-remplir le formulaire
                setFormData({
                    title: data.title,
                    description: data.description,
                    categoryId: data.categoryId,
                    campus: data.campus,
                    type: data.type
                });

                // Sauvegarder l'image actuelle
                if (data.image) {
                    setCurrentImage(data.image);
                }

                setLoadingData(false);
            } catch (err) {
                setError('Erreur lors du chargement de l\'annonce');
                setLoadingData(false);
            }
        };

        fetchAnnonce();
    }, [id]);

    // Gestion des changements de champs
    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    // Gestion de la sélection d'image
    const handleFileChange = (e) => {
        const selectedFile = e.target.files[0];
        
        if (!selectedFile) {
            setFile(null);
            setPreview(null);
            return;
        }

        // Vérification de la taille (2 Mo max)
        if (selectedFile.size > 2 * 1024 * 1024) {
            setError('L\'image ne doit pas dépasser 2 Mo');
            setFile(null);
            setPreview(null);
            return;
        }

        // Vérification du type
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(selectedFile.type)) {
            setError('Formats acceptés : JPG, PNG, WEBP');
            setFile(null);
            setPreview(null);
            return;
        }

        setError('');
        setFile(selectedFile);

        // Créer l'aperçu
        const reader = new FileReader();
        reader.onloadend = () => {
            setPreview(reader.result);
        };
        reader.readAsDataURL(selectedFile);
    };

    // Soumission du formulaire
    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');

        // Validation
        if (!formData.title || !formData.description || !formData.categoryId || !formData.campus) {
            setError('Tous les champs sont obligatoires');
            return;
        }

        // ⚠️ CONFIRMATION CRITIQUE
        const confirmed = window.confirm(
            "⚠️ ATTENTION ⚠️\n\n" +
            "Toute modification de votre annonce nécessitera une nouvelle validation par un modérateur.\n\n" +
            "Votre annonce sera temporairement INVISIBLE jusqu'à validation.\n\n" +
            "Voulez-vous vraiment continuer ?"
        );

        if (!confirmed) {
            return;
        }

        setLoading(true);
        const toastId = toast.loading('Envoi en cours...');

        // Création du FormData
        const formDataToSend = new FormData();
        formDataToSend.append('title', formData.title);
        formDataToSend.append('description', formData.description);
        formDataToSend.append('categoryId', formData.categoryId);
        formDataToSend.append('campus', formData.campus);
        formDataToSend.append('type', formData.type);

        // N'ajouter l'image QUE si une nouvelle a été sélectionnée
        if (file) {
            formDataToSend.append('image', file);
        }

        try {
            const response = await fetch(`/api/annonces/${id}/edit`, {
                method: 'POST',
                body: formDataToSend
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Erreur lors de la modification');
            }

            // Succès : redirection vers "Mes Annonces" avec message
            toast.success('Modifications enregistrées ! Retour en modération.', { id: toastId });
            window.location.href = '/mes-annonces';

        } catch (err) {
            setError(err.message);
            toast.error('Erreur lors de l\'envoi.', { id: toastId });
        } finally {
            setLoading(false);
        }
    };

    if (loadingData) {
        return (
            <div className="text-center py-5">
                <div className="spinner-border text-primary" role="status">
                    <span className="visually-hidden">Chargement...</span>
                </div>
                <p className="mt-3 text-muted">Chargement des données...</p>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit}>
            <Toaster position="top-right" />
            {error && (
                <div className="alert alert-danger" role="alert">
                    <i className="bi bi-exclamation-triangle"></i> {error}
                </div>
            )}

            {/* Titre */}
            <div className="mb-3">
                <label htmlFor="title" className="form-label fw-bold">
                    Titre de l'annonce <span className="text-danger">*</span>
                </label>
                <input
                    type="text"
                    className="form-control"
                    id="title"
                    name="title"
                    value={formData.title}
                    onChange={handleChange}
                    placeholder="Ex: Vélo tout terrain, Canapé convertible..."
                    maxLength={100}
                    required
                />
                <small className="text-muted">{formData.title.length}/100 caractères</small>
            </div>

            {/* Campus */}
            <div className="mb-3">
                <label htmlFor="campus" className="form-label fw-bold">
                    Campus <span className="text-danger">*</span>
                </label>
                <select
                    className="form-select"
                    id="campus"
                    name="campus"
                    value={formData.campus}
                    onChange={handleChange}
                    required
                >
                    <option value="">-- Sélectionnez votre campus --</option>
                    <option value="BOULOGNE">Boulogne-sur-Mer</option>
                    <option value="CALAIS">Calais</option>
                    <option value="DUNKERQUE">Dunkerque</option>
                    <option value="SAINT_OMER">Saint-Omer</option>
                </select>
            </div>

            {/* Catégorie */}
            <div className="mb-3">
                <label htmlFor="categoryId" className="form-label fw-bold">
                    Catégorie <span className="text-danger">*</span>
                </label>
                <select
                    className="form-select"
                    id="categoryId"
                    name="categoryId"
                    value={formData.categoryId}
                    onChange={handleChange}
                    required
                >
                    <option value="">-- Sélectionnez une catégorie --</option>
                    {categories.map(cat => (
                        <option key={cat.id} value={cat.id}>{cat.name}</option>
                    ))}
                </select>
            </div>

            {/* Type (Don ou Troc) */}
            <div className="mb-3">
                <label className="form-label fw-bold">
                    Type d'annonce <span className="text-danger">*</span>
                </label>
                <div>
                    <div className="form-check form-check-inline">
                        <input
                            className="form-check-input"
                            type="radio"
                            name="type"
                            id="typeDon"
                            value="DON"
                            checked={formData.type === 'DON'}
                            onChange={handleChange}
                        />
                        <label className="form-check-label" htmlFor="typeDon">
                            🎁 Don (gratuit)
                        </label>
                    </div>
                    <div className="form-check form-check-inline">
                        <input
                            className="form-check-input"
                            type="radio"
                            name="type"
                            id="typeTroc"
                            value="TROC"
                            checked={formData.type === 'TROC'}
                            onChange={handleChange}
                        />
                        <label className="form-check-label" htmlFor="typeTroc">
                            🔄 Troc (échange)
                        </label>
                    </div>
                </div>
            </div>

            {/* Description */}
            <div className="mb-3">
                <label htmlFor="description" className="form-label fw-bold">
                    Description <span className="text-danger">*</span>
                </label>
                <textarea
                    className="form-control"
                    id="description"
                    name="description"
                    rows="5"
                    value={formData.description}
                    onChange={handleChange}
                    placeholder="Décrivez votre objet (état, dimensions, etc.)"
                    maxLength={1000}
                    required
                ></textarea>
                <small className="text-muted">{formData.description.length}/1000 caractères</small>
            </div>

            {/* Image actuelle */}
            {currentImage && !preview && (
                <div className="mb-3">
                    <label className="form-label fw-bold">Image actuelle</label>
                    <div className="border rounded p-3 bg-light">
                        <img
                            src={currentImage}
                            alt="Image actuelle"
                            className="img-fluid rounded"
                            style={{ maxHeight: '200px', objectFit: 'cover' }}
                        />
                        <p className="text-muted small mt-2 mb-0">
                            <i className="bi bi-info-circle"></i> Vous pouvez remplacer cette image ci-dessous
                        </p>
                    </div>
                </div>
            )}

            {/* Upload nouvelle image */}
            <div className="mb-3">
                <label htmlFor="image" className="form-label fw-bold">
                    {currentImage ? 'Remplacer l\'image (optionnel)' : 'Image *'}
                </label>
                <input
                    type="file"
                    className="form-control"
                    id="image"
                    accept="image/jpeg,image/png,image/webp"
                    onChange={handleFileChange}
                />
                <small className="text-muted d-block mt-1">
                    Formats acceptés : JPG, PNG, WEBP | Taille max : 2 Mo
                </small>
            </div>

            {/* Prévisualisation nouvelle image */}
            {preview && (
                <div className="mb-3">
                    <label className="form-label fw-bold">Nouvelle image (prévisualisation)</label>
                    <div className="border rounded p-3 bg-light">
                        <img
                            src={preview}
                            alt="Prévisualisation"
                            className="img-fluid rounded"
                            style={{ maxHeight: '300px', objectFit: 'contain' }}
                        />
                    </div>
                </div>
            )}

            {/* Boutons */}
            <div className="d-flex gap-2 justify-content-end mt-4">
                <a href="/mes-annonces" className="btn btn-secondary">
                    <i className="bi bi-x-circle"></i> Annuler
                </a>
                <button
                    type="submit"
                    className="btn btn-warning"
                    disabled={loading}
                >
                    {loading ? (
                        <>
                            <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                            Modification en cours...
                        </>
                    ) : (
                        <>
                            <i className="bi bi-check-circle"></i> Modifier l'annonce
                        </>
                    )}
                </button>
            </div>
        </form>
    );
}

import React, { useState, useEffect } from 'react';
import toast, { Toaster } from 'react-hot-toast';

export default function AnnonceForm() {
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [campus, setCampus] = useState('');
    const [type, setType] = useState('DON');
    const [file, setFile] = useState(null);
    const [preview, setPreview] = useState(null);
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    // Charger les catégories au montage du composant
    useEffect(() => {
        // Pour l'instant, catégories en dur (à remplacer par un fetch si besoin)
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
        setSuccess('');

        // Validation
        if (!title || !description || !categoryId || !campus || !file) {
            setError('Tous les champs sont obligatoires');
            return;
        }

        setLoading(true);
        const toastId = toast.loading('Envoi en cours...');

        // Création du FormData
        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', description);
        formData.append('categoryId', categoryId);
        formData.append('campus', campus);
        formData.append('type', type);
        formData.append('image', file);

        try {
            const response = await fetch('/api/annonces/new', {
                method: 'POST',
                body: formData
                // PAS de Content-Type header, le navigateur le gère automatiquement
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
            toast.error('Erreur lors de l\'envoi.', { id: toastId });
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

                                {/* Description */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold">
                                        Description <span className="text-danger">*</span>
                                    </label>
                                    <textarea
                                        className="form-control"
                                        rows="4"
                                        value={description}
                                        onChange={(e) => setDescription(e.target.value)}
                                        placeholder="Décrivez votre objet en détail..."
                                        required
                                    ></textarea>
                                </div>

                                {/* Catégorie */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold">
                                        Catégorie <span className="text-danger">*</span>
                                    </label>
                                    <select
                                        className="form-select"
                                        value={categoryId}
                                        onChange={(e) => setCategoryId(e.target.value)}
                                        required
                                    >
                                        <option value="">Choisir une catégorie...</option>
                                        {categories.map((cat) => (
                                            <option key={cat.id} value={cat.id}>
                                                {cat.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Campus */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold">
                                        Campus <span className="text-danger">*</span>
                                    </label>
                                    <select
                                        className="form-select"
                                        value={campus}
                                        onChange={(e) => setCampus(e.target.value)}
                                        required
                                    >
                                        <option value="">Sélectionner un campus...</option>
                                        <option value="CALAIS">📍 Calais</option>
                                        <option value="DUNKERQUE">📍 Dunkerque</option>
                                        <option value="BOULOGNE">📍 Boulogne-sur-Mer</option>
                                        <option value="SAINT_OMER">📍 Saint-Omer</option>
                                    </select>
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

                                {/* Image */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold">
                                        Photo de l'objet <span className="text-danger">*</span>
                                    </label>
                                    <input
                                        type="file"
                                        className="form-control"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handleFileChange}
                                        required
                                    />
                                    <small className="text-muted">
                                        Formats acceptés : JPG, PNG, WEBP • Taille max : 2 Mo
                                    </small>

                                    {/* Aperçu de l'image */}
                                    {preview && (
                                        <div className="mt-3">
                                            <p className="fw-bold mb-2">Aperçu :</p>
                                            <img
                                                src={preview}
                                                alt="Aperçu"
                                                className="img-fluid rounded border"
                                                style={{ maxHeight: '300px' }}
                                            />
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
                                                Déposer l'annonce
                                            </>
                                        )}
                                    </button>
                                    <a href="/home" className="btn btn-outline-secondary">
                                        <i className="bi bi-x-circle me-2"></i>
                                        Annuler
                                    </a>
                                </div>

                                <div className="alert alert-info mt-3 mb-0">
                                    <small>
                                        <i className="bi bi-info-circle me-2"></i>
                                        Votre annonce sera vérifiée par un modérateur avant publication.
                                    </small>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

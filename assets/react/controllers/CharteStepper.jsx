import React, { useState } from 'react';
import toast, { Toaster } from 'react-hot-toast';

// Les 4 sections de la charte
const CHARTE_SECTIONS = [
    {
        id: 1,
        title: "Esprit de la plateforme (Don et Troc)",
        content: `
            ULC'OCCAZ est une plateforme collaborative où les étudiants peuvent donner ou troquer des objets.

            **Principes fondamentaux :**
            • Partage solidaire et gratuit (pour les dons)
            • Échange équitable (pour les trocs)
            • Entraide communautaire au sein de l'université
            • Réduction des gaspillages
            • Création d'une économie circulaire étudiante

            En acceptant cette charte, vous vous engagez à participer activement à cette communauté 
            d'entraide et à contribuer positivement au partage des ressources.
        `
    },
    {
        id: 2,
        title: "Objets interdits et limites",
        content: `
            **Objets strictement interdits :**
            • Armes, explosifs, matériaux dangereux
            • Drogues et substances illicites
            • Articles contrefaits ou volés
            • Matériels à caractère violent ou discriminatoire
            • Produits alimentaires périssables
            • Médicaments et produits chimiques

            **Objets soumis à restrictions :**
            • Les appareils électriques doivent être fonctionnels
            • Les manuels scolaires doivent être en bon état de lecture
            • Les vêtements doivent être propres et sans taches

            La plateforme se réserve le droit de supprimer tout objet non conforme.
        `
    },
    {
        id: 3,
        title: "Respect, courtoisie et rendez-vous",
        content: `
            **Comportement attendu :**
            • Respecter les descriptions des objets et ne pas décrire malhonnêtement
            • Communiquer poliment avec les autres utilisateurs
            • Honorer vos engagements de rendez-vous
            • Prendre soin des objets reçus
            • Fournir une photo ou description honnête de l'objet

            **Rendez-vous sécurisés :**
            • Les échanges doivent se faire dans un lieu public
            • Préférez les campus ou espaces communs
            • En cas de désaccord, contactez l'équipe de modération

            Les utilisateurs ne respectant pas ces règles peuvent être bannis de la plateforme.
        `
    },
    {
        id: 4,
        title: "Responsabilité de l'ULCO",
        content: `
            **Exonération de responsabilité :**
            • L'ULCO n'est pas responsable de la qualité des objets échangés
            • L'ULCO ne couvre pas les litiges de paiement ou d'échange
            • Chaque transaction est de responsabilité personnelle entre utilisateurs
            • La plateforme ne garantit pas la légitimité des comptes

            **Modération :**
            • Notre équipe modère les annonces et les comptes
            • Les violations seront sanctionnées (suppression, bannissement)
            • Nous nous réservons le droit de supprimer tout contenu offensant

            En utilisant cette plateforme, vous acceptez ces conditions et absolvez l'ULCO 
            de toute responsabilité liée aux transactions directes entre utilisateurs.
        `
    }
];

export default function CharteStepper() {
    const [currentStep, setCurrentStep] = useState(0);
    const [loading, setLoading] = useState(false);
    const [acceptedSections, setAcceptedSections] = useState(new Set());

    const section = CHARTE_SECTIONS[currentStep];
    const isLastStep = currentStep === CHARTE_SECTIONS.length - 1;
    const isSectionAccepted = acceptedSections.has(currentStep);

    const handleAcceptSection = () => {
        const newAccepted = new Set(acceptedSections);
        newAccepted.add(currentStep);
        setAcceptedSections(newAccepted);

        if (isLastStep) {
            handleFinalAccept();
        } else {
            setCurrentStep(currentStep + 1);
        }
    };

    const handleFinalAccept = async () => {
        console.log('=== DÉBUT handleFinalAccept ===');
        console.log('acceptedSections:', acceptedSections);
        console.log('sections to send:', Array.from(acceptedSections).map(idx => CHARTE_SECTIONS[idx].title));
        
        setLoading(true);
        try {
            const response = await fetch('/api/user/charte/accept', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sections: Array.from(acceptedSections).map(idx => CHARTE_SECTIONS[idx].title)
                })
            });

            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);

            if (!response.ok) {
                const data = await response.json();
                console.log('Error data:', data);
                throw new Error(data.message || 'Erreur lors de l\'acceptation de la charte');
            }

            const result = await response.json();
            console.log('Success result:', result);
            toast.success('Charte acceptée avec succès !');
            
            // Redirection vers la page d'accueil après 0.5 seconde
            setTimeout(() => {
                window.location.href = '/home';
            }, 500);
        } catch (error) {
            console.error('Erreur:', error);
            toast.error(error.message || 'Erreur lors de l\'acceptation de la charte');
            setLoading(false);
        }
    };

    return (
        <div className="min-vh-100 d-flex align-items-center justify-content-center" 
             style={{ backgroundColor: '#f8f9fa' }}>
            <Toaster position="top-right" />
            
            <div className="container" style={{ maxWidth: '700px' }}>
                <div className="card shadow-lg border-0 rounded-4">
                    {/* En-tête avec logo et titre */}
                    <div style={{ backgroundColor: '#001a33', color: 'white', padding: '3rem 2rem' }}>
                        <h1 className="h2 mb-2">
                            <i className="bi bi-file-earmark-check me-2"></i>
                            Charte ULC'OCCAZ
                        </h1>
                        <p className="text-white-50 mb-0">
                            Acceptez notre charte pour accéder à la plateforme
                        </p>
                    </div>

                    {/* Contenu principal */}
                    <div className="card-body p-4 p-md-5">
                        {/* Indicateur de progression */}
                        <div className="mb-4">
                            <div className="d-flex justify-content-between align-items-center mb-3">
                                <h4 className="h5 mb-0" style={{ color: '#001a33' }}>
                                    {section.title}
                                </h4>
                                <span className="badge" style={{ backgroundColor: '#001a33' }}>
                                    Étape {currentStep + 1}/{CHARTE_SECTIONS.length}
                                </span>
                            </div>
                            
                            {/* Barre de progression */}
                            <div className="progress" style={{ height: '8px', backgroundColor: '#e9ecef' }}>
                                <div
                                    className="progress-bar"
                                    style={{
                                        width: `${((currentStep + 1) / CHARTE_SECTIONS.length) * 100}%`,
                                        backgroundColor: '#001a33',
                                        transition: 'width 0.3s ease'
                                    }}
                                ></div>
                            </div>
                        </div>

                        {/* Texte de la section */}
                        <div 
                            className="mb-4 p-3 rounded-3"
                            style={{ 
                                backgroundColor: '#f8f9fa',
                                border: '1px solid #e0e0e0',
                                minHeight: '250px',
                                lineHeight: '1.8'
                            }}
                        >
                            <div style={{ color: '#333', whiteSpace: 'pre-wrap' }}>
                                {section.content}
                            </div>
                        </div>

                        {/* Étapes complétées */}
                        <div className="mb-4">
                            <p className="text-muted small mb-2">Sections acceptées :</p>
                            <div className="d-flex flex-wrap gap-2">
                                {CHARTE_SECTIONS.map((sec, idx) => (
                                    <span
                                        key={idx}
                                        className="badge"
                                        style={{
                                            backgroundColor: acceptedSections.has(idx) ? '#001a33' : '#d3d3d3',
                                            color: 'white'
                                        }}
                                    >
                                        {idx + 1}. {sec.title.split('(')[0].trim()}
                                    </span>
                                ))}
                            </div>
                        </div>

                        {/* Boutons */}
                        <div className="d-grid gap-3">
                            {!isSectionAccepted ? (
                                <button
                                    className="btn btn-lg rounded-3"
                                    style={{
                                        backgroundColor: '#001a33',
                                        color: 'white',
                                        border: 'none',
                                        fontWeight: '600'
                                    }}
                                    onClick={handleAcceptSection}
                                    disabled={loading}
                                >
                                    {loading ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                            Traitement en cours...
                                        </>
                                    ) : (
                                        <>
                                            <i className="bi bi-check-circle me-2"></i>
                                            J'ai lu et j'accepte cette partie
                                        </>
                                    )}
                                </button>
                            ) : (
                                <>
                                    {isLastStep ? (
                                        <button
                                            className="btn btn-lg rounded-3"
                                            style={{
                                                backgroundColor: '#001a33',
                                                color: 'white',
                                                border: 'none',
                                                fontWeight: '600'
                                            }}
                                            onClick={handleFinalAccept}
                                            disabled={loading}
                                        >
                                            {loading ? (
                                                <>
                                                    <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                                                    Finalisation en cours...
                                                </>
                                            ) : (
                                                <>
                                                    <i className="bi bi-check-all me-2"></i>
                                                    Accepter la charte et finaliser mon inscription
                                                </>
                                            )}
                                        </button>
                                    ) : (
                                        <button
                                            className="btn btn-lg rounded-3"
                                            style={{
                                                backgroundColor: '#001a33',
                                                color: 'white',
                                                border: 'none',
                                                fontWeight: '600'
                                            }}
                                            onClick={() => setCurrentStep(currentStep + 1)}
                                        >
                                            <i className="bi bi-chevron-right me-2"></i>
                                            Partie suivante
                                        </button>
                                    )}
                                </>
                            )}

                            {/* Lien de retour (optionnel) */}
                            <a href="/" className="btn btn-outline-secondary btn-lg rounded-3">
                                <i className="bi bi-arrow-left me-2"></i>
                                Revenir à l'accueil
                            </a>
                        </div>
                    </div>

                    {/* Pied de page */}
                    <div 
                        className="card-footer text-center text-muted small"
                        style={{ backgroundColor: '#f8f9fa' }}
                    >
                        <p className="mb-0">
                            En acceptant cette charte, vous acceptez nos conditions d'utilisation.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

import React, { useEffect, useState } from 'react';
import toast, { Toaster } from 'react-hot-toast';

const CAMPUS_OPTIONS = [
    { value: 'CALAIS', label: 'Calais' },
    { value: 'DUNKERQUE', label: 'Dunkerque' },
    { value: 'BOULOGNE', label: 'Boulogne-sur-Mer' },
    { value: 'SAINT_OMER', label: 'Saint-Omer' },
];

export default function UserManager() {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [selectedUser, setSelectedUser] = useState(null);
    const [roleChoice, setRoleChoice] = useState('USER');
    const [campusChoice, setCampusChoice] = useState('');
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        loadUsers();
    }, []);

    const loadUsers = async () => {
        setLoading(true);
        try {
            const response = await fetch('/api/admin/users');
            if (!response.ok) throw new Error('Erreur lors du chargement des utilisateurs');
            const data = await response.json();
            setUsers(data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const getRoleBadge = (roles) => {
        if (roles.includes('ROLE_ADMIN')) {
            return <span className="badge bg-dark">SUPER ADMIN</span>;
        }
        if (roles.includes('ROLE_MODERATOR')) {
            return <span className="badge bg-primary">STAFF</span>;
        }
        return <span className="badge bg-secondary">UTILISATEUR</span>;
    };

    const openRoleModal = (user) => {
        setSelectedUser(user);
        if (user.roles.includes('ROLE_MODERATOR')) {
            setRoleChoice('MODERATOR');
            setCampusChoice(user.moderated_campus || '');
        } else {
            setRoleChoice('USER');
            setCampusChoice('');
        }
        const modal = new window.bootstrap.Modal(document.getElementById('roleModal'));
        modal.show();
    };

    const handleSaveRole = async () => {
        if (!selectedUser) return;

        if (roleChoice === 'MODERATOR' && !campusChoice) {
            setError('Veuillez sélectionner un campus pour le modérateur.');
            return;
        }

        setSaving(true);
        try {
            const response = await fetch(`/api/admin/users/${selectedUser.id}/promote`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    role: roleChoice,
                    campus: roleChoice === 'MODERATOR' ? campusChoice : null,
                }),
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Erreur lors de la mise à jour');
            }

            await loadUsers();
            setSelectedUser(null);
            const modalEl = document.getElementById('roleModal');
            window.bootstrap.Modal.getInstance(modalEl)?.hide();
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    };

    const toggleBan = async (user) => {
        try {
            let options = { method: 'POST' };

            if (!user.is_banned) {
                const reason = window.prompt('Veuillez saisir le motif du bannissement (Obligatoire) :');
                if (!reason || !reason.trim()) {
                    return;
                }

                options = {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ reason: reason.trim() }),
                };
            }

            const response = await fetch(`/api/admin/users/${user.id}/ban`, options);

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Erreur lors du bannissement');
            }

            await loadUsers();
            if (user.is_banned) {
                toast.success('Compte utilisateur réactivé.');
            } else {
                toast.success('Utilisateur banni et notifié par email.');
            }
        } catch (err) {
            setError(err.message);
        }
    };

    if (loading) {
        return (
            <div className="text-center py-5">
                <div className="spinner-border text-primary" role="status"></div>
            </div>
        );
    }

    return (
        <div className="card shadow-sm">
            <Toaster position="top-right" />
            <div className="card-body">
                {error && (
                    <div className="alert alert-danger" role="alert">
                        <i className="bi bi-exclamation-triangle me-2"></i>
                        {error}
                    </div>
                )}

                <div className="table-responsive">
                    <table className="table table-hover align-middle">
                        <thead className="table-light">
                            <tr>
                                <th>Identifiant (CAS)</th>
                                <th>Rôle</th>
                                <th>Campus géré</th>
                                <th>Statut</th>
                                <th className="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.map((user) => (
                                <tr key={user.id}>
                                    <td>
                                        <div className="fw-bold">{user.cas_uid}</div>
                                        <div className="small text-muted">{user.email}</div>
                                    </td>
                                    <td>{getRoleBadge(user.roles)}</td>
                                    <td>{user.moderated_campus || '—'}</td>
                                    <td>
                                        {user.is_banned ? (
                                            <span className="badge bg-danger">BANNI</span>
                                        ) : (
                                            <span className="badge bg-success">ACTIF</span>
                                        )}
                                    </td>
                                    <td className="text-end">
                                        <div className="d-flex gap-2 justify-content-end">
                                            <button
                                                className={user.is_banned ? 'btn btn-sm btn-success' : 'btn btn-sm btn-danger'}
                                                onClick={() => toggleBan(user)}
                                            >
                                                {user.is_banned ? 'Réactiver' : '🚫 Bannir'}
                                            </button>
                                            <button
                                                className="btn btn-sm btn-outline-primary"
                                                onClick={() => openRoleModal(user)}
                                            >
                                                ✏️ Modifier rôle
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="modal fade" id="roleModal" tabIndex="-1" aria-hidden="true">
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title">Modifier rôle</h5>
                            <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div className="modal-body">
                            <div className="mb-3">
                                <label className="form-label">Type de compte</label>
                                <select
                                    className="form-select"
                                    value={roleChoice}
                                    onChange={(e) => setRoleChoice(e.target.value)}
                                >
                                    <option value="USER">Utilisateur (Étudiant / Prof / Personnel)</option>
                                    <option value="MODERATOR">Modérateur Staff</option>
                                </select>
                            </div>

                            {roleChoice === 'MODERATOR' && (
                                <div className="mb-3">
                                    <label className="form-label">Campus géré</label>
                                    <select
                                        className="form-select"
                                        value={campusChoice}
                                        onChange={(e) => setCampusChoice(e.target.value)}
                                    >
                                        <option value="">-- Sélectionner un campus --</option>
                                        {CAMPUS_OPTIONS.map((c) => (
                                            <option key={c.value} value={c.value}>
                                                {c.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">
                                Annuler
                            </button>
                            <button
                                type="button"
                                className="btn btn-primary"
                                onClick={handleSaveRole}
                                disabled={saving}
                            >
                                {saving ? 'Enregistrement...' : 'Enregistrer'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

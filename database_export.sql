--
-- PostgreSQL database dump
--

\restrict xOUqqZcyj71y3hF4UvbKSqWuVAoaZki0d7wKvyNRCDN89ZQMVY5VqAzfJeZjOwE

-- Dumped from database version 16.13 (Ubuntu 16.13-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.13 (Ubuntu 16.13-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.user_favorites DROP CONSTRAINT IF EXISTS fk_user_favorites_user;
ALTER TABLE IF EXISTS ONLY public.user_favorites DROP CONSTRAINT IF EXISTS fk_user_favorites_annonce;
ALTER TABLE IF EXISTS ONLY public.annonce DROP CONSTRAINT IF EXISTS fk_f65593e57e3c61f9;
ALTER TABLE IF EXISTS ONLY public.annonce DROP CONSTRAINT IF EXISTS fk_f65593e57a88e00;
ALTER TABLE IF EXISTS ONLY public.annonce DROP CONSTRAINT IF EXISTS fk_f65593e512469de2;
ALTER TABLE IF EXISTS ONLY public.annonce_image DROP CONSTRAINT IF EXISTS fk_d2b0cfc08805ab2f;
ALTER TABLE IF EXISTS ONLY public.notification DROP CONSTRAINT IF EXISTS fk_bf5476caa76ed395;
ALTER TABLE IF EXISTS ONLY public.message DROP CONSTRAINT IF EXISTS fk_b6bd307ff624b39d;
ALTER TABLE IF EXISTS ONLY public.message DROP CONSTRAINT IF EXISTS fk_b6bd307f9ac0396;
ALTER TABLE IF EXISTS ONLY public.conversation DROP CONSTRAINT IF EXISTS fk_8a8e26e98805ab2f;
ALTER TABLE IF EXISTS ONLY public.conversation DROP CONSTRAINT IF EXISTS fk_8a8e26e96c755722;
ALTER TABLE IF EXISTS ONLY public.transaction DROP CONSTRAINT IF EXISTS fk_723705d1cd53edb6;
ALTER TABLE IF EXISTS ONLY public.transaction DROP CONSTRAINT IF EXISTS fk_723705d18805ab2f;
ALTER TABLE IF EXISTS ONLY public.charte_agreement DROP CONSTRAINT IF EXISTS fk_3c434f6aa76ed395;
ALTER TABLE IF EXISTS ONLY public.conversation_participants DROP CONSTRAINT IF EXISTS fk_21821ed3a76ed395;
ALTER TABLE IF EXISTS ONLY public.conversation_participants DROP CONSTRAINT IF EXISTS fk_21821ed39ac0396;
DROP INDEX IF EXISTS public.unique_buyer_annonce;
DROP INDEX IF EXISTS public.uniq_user_alias;
DROP INDEX IF EXISTS public.uniq_8d93d649655c0f06;
DROP INDEX IF EXISTS public.uniq_723705d18805ab2f;
DROP INDEX IF EXISTS public.uniq_64c19c1989d9b62;
DROP INDEX IF EXISTS public.idx_user_favorites_annonce;
DROP INDEX IF EXISTS public.idx_f65593e57e3c61f9;
DROP INDEX IF EXISTS public.idx_f65593e57a88e00;
DROP INDEX IF EXISTS public.idx_f65593e512469de2;
DROP INDEX IF EXISTS public.idx_d2b0cfc08805ab2f;
DROP INDEX IF EXISTS public.idx_bf5476caa76ed395;
DROP INDEX IF EXISTS public.idx_b6bd307ff624b39d;
DROP INDEX IF EXISTS public.idx_b6bd307f9ac0396;
DROP INDEX IF EXISTS public.idx_8a8e26e98805ab2f;
DROP INDEX IF EXISTS public.idx_8a8e26e96c755722;
DROP INDEX IF EXISTS public.idx_75ea56e0fb7336f0e3bd61ce16ba31dbbf396750;
DROP INDEX IF EXISTS public.idx_723705d1cd53edb6;
DROP INDEX IF EXISTS public.idx_3c434f6aa76ed395;
DROP INDEX IF EXISTS public.idx_21821ed3a76ed395;
DROP INDEX IF EXISTS public.idx_21821ed39ac0396;
ALTER TABLE IF EXISTS ONLY public."user" DROP CONSTRAINT IF EXISTS user_pkey;
ALTER TABLE IF EXISTS ONLY public.user_favorites DROP CONSTRAINT IF EXISTS user_favorites_pkey;
ALTER TABLE IF EXISTS ONLY public.transaction DROP CONSTRAINT IF EXISTS transaction_pkey;
ALTER TABLE IF EXISTS ONLY public.notification DROP CONSTRAINT IF EXISTS notification_pkey;
ALTER TABLE IF EXISTS ONLY public.messenger_messages DROP CONSTRAINT IF EXISTS messenger_messages_pkey;
ALTER TABLE IF EXISTS ONLY public.message DROP CONSTRAINT IF EXISTS message_pkey;
ALTER TABLE IF EXISTS ONLY public.doctrine_migration_versions DROP CONSTRAINT IF EXISTS doctrine_migration_versions_pkey;
ALTER TABLE IF EXISTS ONLY public.conversation DROP CONSTRAINT IF EXISTS conversation_pkey;
ALTER TABLE IF EXISTS ONLY public.conversation_participants DROP CONSTRAINT IF EXISTS conversation_participants_pkey;
ALTER TABLE IF EXISTS ONLY public.charte_agreement DROP CONSTRAINT IF EXISTS charte_agreement_pkey;
ALTER TABLE IF EXISTS ONLY public.category DROP CONSTRAINT IF EXISTS category_pkey;
ALTER TABLE IF EXISTS ONLY public.annonce DROP CONSTRAINT IF EXISTS annonce_pkey;
ALTER TABLE IF EXISTS ONLY public.annonce_image DROP CONSTRAINT IF EXISTS annonce_image_pkey;
DROP TABLE IF EXISTS public.user_favorites;
DROP TABLE IF EXISTS public."user";
DROP TABLE IF EXISTS public.transaction;
DROP TABLE IF EXISTS public.notification;
DROP TABLE IF EXISTS public.messenger_messages;
DROP TABLE IF EXISTS public.message;
DROP TABLE IF EXISTS public.doctrine_migration_versions;
DROP TABLE IF EXISTS public.conversation_participants;
DROP TABLE IF EXISTS public.conversation;
DROP TABLE IF EXISTS public.charte_agreement;
DROP TABLE IF EXISTS public.category;
DROP TABLE IF EXISTS public.annonce_image;
DROP TABLE IF EXISTS public.annonce;
SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: annonce; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.annonce (
    id uuid NOT NULL,
    title character varying(255) NOT NULL,
    description text NOT NULL,
    type character varying(255) NOT NULL,
    state character varying(255) NOT NULL,
    expires_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    owner_id uuid NOT NULL,
    category_id integer NOT NULL,
    created_at timestamp(0) without time zone NOT NULL,
    refusal_reason text,
    locked_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    locked_by_id uuid,
    custom_category_name character varying(100) DEFAULT NULL::character varying,
    campuses json NOT NULL
);


ALTER TABLE public.annonce OWNER TO plateforme_user;

--
-- Name: annonce_image; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.annonce_image (
    id integer NOT NULL,
    image_name character varying(255) NOT NULL,
    annonce_id uuid NOT NULL
);


ALTER TABLE public.annonce_image OWNER TO plateforme_user;

--
-- Name: annonce_image_id_seq; Type: SEQUENCE; Schema: public; Owner: plateforme_user
--

ALTER TABLE public.annonce_image ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.annonce_image_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: category; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.category (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL
);


ALTER TABLE public.category OWNER TO plateforme_user;

--
-- Name: category_id_seq; Type: SEQUENCE; Schema: public; Owner: plateforme_user
--

ALTER TABLE public.category ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.category_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: charte_agreement; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.charte_agreement (
    id integer NOT NULL,
    section_name character varying(255) NOT NULL,
    agreed_at timestamp(0) without time zone NOT NULL,
    user_id uuid NOT NULL
);


ALTER TABLE public.charte_agreement OWNER TO plateforme_user;

--
-- Name: charte_agreement_id_seq; Type: SEQUENCE; Schema: public; Owner: plateforme_user
--

ALTER TABLE public.charte_agreement ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.charte_agreement_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: conversation; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.conversation (
    id uuid NOT NULL,
    annonce_id uuid NOT NULL,
    buyer_id uuid NOT NULL,
    created_at timestamp(0) without time zone NOT NULL,
    updated_at timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.conversation OWNER TO plateforme_user;

--
-- Name: conversation_participants; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.conversation_participants (
    conversation_id uuid NOT NULL,
    user_id uuid NOT NULL
);


ALTER TABLE public.conversation_participants OWNER TO plateforme_user;

--
-- Name: doctrine_migration_versions; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.doctrine_migration_versions (
    version character varying(191) NOT NULL,
    executed_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    execution_time integer
);


ALTER TABLE public.doctrine_migration_versions OWNER TO plateforme_user;

--
-- Name: message; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.message (
    id integer NOT NULL,
    content text NOT NULL,
    is_read boolean NOT NULL,
    created_at timestamp(0) without time zone NOT NULL,
    conversation_id uuid NOT NULL,
    sender_id uuid NOT NULL
);


ALTER TABLE public.message OWNER TO plateforme_user;

--
-- Name: message_id_seq; Type: SEQUENCE; Schema: public; Owner: plateforme_user
--

ALTER TABLE public.message ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.message_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: messenger_messages; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.messenger_messages (
    id bigint NOT NULL,
    body text NOT NULL,
    headers text NOT NULL,
    queue_name character varying(190) NOT NULL,
    created_at timestamp(0) without time zone NOT NULL,
    available_at timestamp(0) without time zone NOT NULL,
    delivered_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone
);


ALTER TABLE public.messenger_messages OWNER TO plateforme_user;

--
-- Name: messenger_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: plateforme_user
--

ALTER TABLE public.messenger_messages ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.messenger_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: notification; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.notification (
    id integer NOT NULL,
    message character varying(255) NOT NULL,
    type character varying(50) NOT NULL,
    link character varying(255) NOT NULL,
    is_read boolean NOT NULL,
    created_at timestamp(0) without time zone NOT NULL,
    user_id uuid NOT NULL
);


ALTER TABLE public.notification OWNER TO plateforme_user;

--
-- Name: notification_id_seq; Type: SEQUENCE; Schema: public; Owner: plateforme_user
--

ALTER TABLE public.notification ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.notification_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: transaction; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.transaction (
    id integer NOT NULL,
    giver_validated boolean NOT NULL,
    receiver_validated boolean NOT NULL,
    rating integer,
    annonce_id uuid NOT NULL,
    receiver_id uuid NOT NULL
);


ALTER TABLE public.transaction OWNER TO plateforme_user;

--
-- Name: transaction_id_seq; Type: SEQUENCE; Schema: public; Owner: plateforme_user
--

ALTER TABLE public.transaction ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.transaction_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: user; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public."user" (
    id uuid NOT NULL,
    cas_uid character varying(180) NOT NULL,
    email character varying(255) NOT NULL,
    roles json NOT NULL,
    is_banned boolean NOT NULL,
    created_at timestamp(0) without time zone NOT NULL,
    password character varying(255) DEFAULT NULL::character varying,
    alias character varying(20) DEFAULT NULL::character varying
);


ALTER TABLE public."user" OWNER TO plateforme_user;

--
-- Name: user_favorites; Type: TABLE; Schema: public; Owner: plateforme_user
--

CREATE TABLE public.user_favorites (
    user_id uuid NOT NULL,
    annonce_id uuid NOT NULL
);


ALTER TABLE public.user_favorites OWNER TO plateforme_user;

--
-- Data for Name: annonce; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.annonce (id, title, description, type, state, expires_at, owner_id, category_id, created_at, refusal_reason, locked_at, locked_by_id, custom_category_name, campuses) FROM stdin;
\.


--
-- Data for Name: annonce_image; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.annonce_image (id, image_name, annonce_id) FROM stdin;
\.


--
-- Data for Name: category; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.category (id, name, slug) FROM stdin;
1	Livres	livres
2	Matériel Informatique	materielinformatique
3	Mobilier	mobilier
4	Vêtements	vetements
5	Électroménager	lectromenager
6	Vaisselle	vaisselle
7	Fournitures Scolaires	fournituresscolaires
8	Sport	sport
\.


--
-- Data for Name: charte_agreement; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.charte_agreement (id, section_name, agreed_at, user_id) FROM stdin;
\.


--
-- Data for Name: conversation; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.conversation (id, annonce_id, buyer_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: conversation_participants; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.conversation_participants (conversation_id, user_id) FROM stdin;
\.


--
-- Data for Name: doctrine_migration_versions; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.doctrine_migration_versions (version, executed_at, execution_time) FROM stdin;
DoctrineMigrations\\Version20260130185539	2026-03-30 19:10:55	27
DoctrineMigrations\\Version20260130185752	2026-03-30 19:10:55	196
DoctrineMigrations\\Version20260130194151	2026-03-30 19:10:55	2
DoctrineMigrations\\Version20260130220430	2026-03-30 19:10:55	2
DoctrineMigrations\\Version20260130233412	2026-03-30 19:10:55	2
DoctrineMigrations\\Version20260201102502	2026-03-30 19:10:55	52
DoctrineMigrations\\Version20260201140000	2026-03-30 19:10:55	17
DoctrineMigrations\\Version20260221092811	2026-03-30 19:10:55	1
DoctrineMigrations\\Version20260223111306	2026-03-30 19:10:55	13
DoctrineMigrations\\Version20260223125216	2026-03-30 19:10:55	1
DoctrineMigrations\\Version20260228113530	2026-03-30 19:10:55	3
DoctrineMigrations\\Version20260307001000	2026-03-30 19:10:55	7
DoctrineMigrations\\Version20260307003000	2026-03-30 19:10:55	16
\.


--
-- Data for Name: message; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.message (id, content, is_read, created_at, conversation_id, sender_id) FROM stdin;
\.


--
-- Data for Name: messenger_messages; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.messenger_messages (id, body, headers, queue_name, created_at, available_at, delivered_at) FROM stdin;
\.


--
-- Data for Name: notification; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.notification (id, message, type, link, is_read, created_at, user_id) FROM stdin;
\.


--
-- Data for Name: transaction; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.transaction (id, giver_validated, receiver_validated, rating, annonce_id, receiver_id) FROM stdin;
\.


--
-- Data for Name: user; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public."user" (id, cas_uid, email, roles, is_banned, created_at, password, alias) FROM stdin;
99a8925b-8ed9-48fe-a137-e784526ab49b	aresponsable	alice.responsable@univ-littoral.fr	["ROLE_RESPONSABLE","ROLE_MODERATOR","ROLE_USER"]	f	2026-03-30 19:11:10	$2y$13$Oh9NpOBS8aeTgs8nRgc0feoue7Q0NHcPJ5z57cFq9h86IUGn1WOBS	\N
7802acc2-7767-412d-8ee8-e25af4985b74	bresponsable	bob.responsable@univ-littoral.fr	["ROLE_RESPONSABLE","ROLE_MODERATOR","ROLE_USER"]	f	2026-03-30 19:11:11	$2y$13$W0OXLmZxK6KL8UwOgy2XA.alzgwikOH4tFFJ87k9DX5tZKXMllwnu	\N
800c903d-1988-403a-908c-f2c09754110e	jmoderator	jean.moderator@univ-littoral.fr	["ROLE_MODERATOR","ROLE_USER"]	f	2026-03-30 19:11:12	$2y$13$qQgu8LMwGsoVxXuhIY52SuLXY8dfjcquI0VdMeaeefLzpPQtiujMC	\N
6e04966b-9d28-4783-a4e4-b9f0b95c419c	mmoderator	marie.moderator@univ-littoral.fr	["ROLE_MODERATOR","ROLE_USER"]	f	2026-03-30 19:11:12	$2y$13$WBVJu6SaVObbcqYzZcoWJud929gEUXtsMMj30st/8FdJf8z7t0pca	\N
9414c683-6f1f-4372-b10a-fd780f6562b8	sleroy	sophie.leroy@etu.univ-littoral.fr	["ROLE_USER"]	f	2026-03-30 19:11:13	$2y$13$no/yV8NyqKOdoALGEvTEjuk.iN6rKXnmboVXQxnvkk3AivFUMl2ra	\N
f52239cf-6397-48aa-a493-a2167bcda3bd	epetit	emma.petit@etu.univ-littoral.fr	["ROLE_USER"]	f	2026-03-30 19:11:14	$2y$13$hitw6m0mU0JlLOmJFMr4c.pQV1nCxU7CpppfYs2IkpfgS.l80qMhe	\N
b78d88f2-e8cc-4b27-ba64-d1466a068591	hmoreau	hugo.moreau@etu.univ-littoral.fr	["ROLE_USER"]	f	2026-03-30 19:11:15	$2y$13$YN.O79IWpVEP5BvMvpe8LO/9OK.Q.VzKIQCBAqz1V.8zdfPMebXaO	\N
32dd2056-c066-4c1d-bd50-d5c6f84cd96f	ldubois	lucas.dubois@etu.univ-littoral.fr	["ROLE_USER"]	f	2026-03-30 19:11:15	$2y$13$qb0QOH2.h6n3tvP1vFysqewO7Rtdq51kTfMZCsvXDLGP0gpcMYBhC	\N
\.


--
-- Data for Name: user_favorites; Type: TABLE DATA; Schema: public; Owner: plateforme_user
--

COPY public.user_favorites (user_id, annonce_id) FROM stdin;
\.


--
-- Name: annonce_image_id_seq; Type: SEQUENCE SET; Schema: public; Owner: plateforme_user
--

SELECT pg_catalog.setval('public.annonce_image_id_seq', 1, false);


--
-- Name: category_id_seq; Type: SEQUENCE SET; Schema: public; Owner: plateforme_user
--

SELECT pg_catalog.setval('public.category_id_seq', 8, true);


--
-- Name: charte_agreement_id_seq; Type: SEQUENCE SET; Schema: public; Owner: plateforme_user
--

SELECT pg_catalog.setval('public.charte_agreement_id_seq', 1, false);


--
-- Name: message_id_seq; Type: SEQUENCE SET; Schema: public; Owner: plateforme_user
--

SELECT pg_catalog.setval('public.message_id_seq', 1, false);


--
-- Name: messenger_messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: plateforme_user
--

SELECT pg_catalog.setval('public.messenger_messages_id_seq', 1, false);


--
-- Name: notification_id_seq; Type: SEQUENCE SET; Schema: public; Owner: plateforme_user
--

SELECT pg_catalog.setval('public.notification_id_seq', 1, false);


--
-- Name: transaction_id_seq; Type: SEQUENCE SET; Schema: public; Owner: plateforme_user
--

SELECT pg_catalog.setval('public.transaction_id_seq', 1, false);


--
-- Name: annonce_image annonce_image_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.annonce_image
    ADD CONSTRAINT annonce_image_pkey PRIMARY KEY (id);


--
-- Name: annonce annonce_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.annonce
    ADD CONSTRAINT annonce_pkey PRIMARY KEY (id);


--
-- Name: category category_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.category
    ADD CONSTRAINT category_pkey PRIMARY KEY (id);


--
-- Name: charte_agreement charte_agreement_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.charte_agreement
    ADD CONSTRAINT charte_agreement_pkey PRIMARY KEY (id);


--
-- Name: conversation_participants conversation_participants_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.conversation_participants
    ADD CONSTRAINT conversation_participants_pkey PRIMARY KEY (conversation_id, user_id);


--
-- Name: conversation conversation_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.conversation
    ADD CONSTRAINT conversation_pkey PRIMARY KEY (id);


--
-- Name: doctrine_migration_versions doctrine_migration_versions_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.doctrine_migration_versions
    ADD CONSTRAINT doctrine_migration_versions_pkey PRIMARY KEY (version);


--
-- Name: message message_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.message
    ADD CONSTRAINT message_pkey PRIMARY KEY (id);


--
-- Name: messenger_messages messenger_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.messenger_messages
    ADD CONSTRAINT messenger_messages_pkey PRIMARY KEY (id);


--
-- Name: notification notification_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.notification
    ADD CONSTRAINT notification_pkey PRIMARY KEY (id);


--
-- Name: transaction transaction_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.transaction
    ADD CONSTRAINT transaction_pkey PRIMARY KEY (id);


--
-- Name: user_favorites user_favorites_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.user_favorites
    ADD CONSTRAINT user_favorites_pkey PRIMARY KEY (user_id, annonce_id);


--
-- Name: user user_pkey; Type: CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public."user"
    ADD CONSTRAINT user_pkey PRIMARY KEY (id);


--
-- Name: idx_21821ed39ac0396; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_21821ed39ac0396 ON public.conversation_participants USING btree (conversation_id);


--
-- Name: idx_21821ed3a76ed395; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_21821ed3a76ed395 ON public.conversation_participants USING btree (user_id);


--
-- Name: idx_3c434f6aa76ed395; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_3c434f6aa76ed395 ON public.charte_agreement USING btree (user_id);


--
-- Name: idx_723705d1cd53edb6; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_723705d1cd53edb6 ON public.transaction USING btree (receiver_id);


--
-- Name: idx_75ea56e0fb7336f0e3bd61ce16ba31dbbf396750; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_75ea56e0fb7336f0e3bd61ce16ba31dbbf396750 ON public.messenger_messages USING btree (queue_name, available_at, delivered_at, id);


--
-- Name: idx_8a8e26e96c755722; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_8a8e26e96c755722 ON public.conversation USING btree (buyer_id);


--
-- Name: idx_8a8e26e98805ab2f; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_8a8e26e98805ab2f ON public.conversation USING btree (annonce_id);


--
-- Name: idx_b6bd307f9ac0396; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_b6bd307f9ac0396 ON public.message USING btree (conversation_id);


--
-- Name: idx_b6bd307ff624b39d; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_b6bd307ff624b39d ON public.message USING btree (sender_id);


--
-- Name: idx_bf5476caa76ed395; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_bf5476caa76ed395 ON public.notification USING btree (user_id);


--
-- Name: idx_d2b0cfc08805ab2f; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_d2b0cfc08805ab2f ON public.annonce_image USING btree (annonce_id);


--
-- Name: idx_f65593e512469de2; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_f65593e512469de2 ON public.annonce USING btree (category_id);


--
-- Name: idx_f65593e57a88e00; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_f65593e57a88e00 ON public.annonce USING btree (locked_by_id);


--
-- Name: idx_f65593e57e3c61f9; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_f65593e57e3c61f9 ON public.annonce USING btree (owner_id);


--
-- Name: idx_user_favorites_annonce; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE INDEX idx_user_favorites_annonce ON public.user_favorites USING btree (annonce_id);


--
-- Name: uniq_64c19c1989d9b62; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE UNIQUE INDEX uniq_64c19c1989d9b62 ON public.category USING btree (slug);


--
-- Name: uniq_723705d18805ab2f; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE UNIQUE INDEX uniq_723705d18805ab2f ON public.transaction USING btree (annonce_id);


--
-- Name: uniq_8d93d649655c0f06; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE UNIQUE INDEX uniq_8d93d649655c0f06 ON public."user" USING btree (cas_uid);


--
-- Name: uniq_user_alias; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE UNIQUE INDEX uniq_user_alias ON public."user" USING btree (alias);


--
-- Name: unique_buyer_annonce; Type: INDEX; Schema: public; Owner: plateforme_user
--

CREATE UNIQUE INDEX unique_buyer_annonce ON public.conversation USING btree (buyer_id, annonce_id);


--
-- Name: conversation_participants fk_21821ed39ac0396; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.conversation_participants
    ADD CONSTRAINT fk_21821ed39ac0396 FOREIGN KEY (conversation_id) REFERENCES public.conversation(id) ON DELETE CASCADE;


--
-- Name: conversation_participants fk_21821ed3a76ed395; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.conversation_participants
    ADD CONSTRAINT fk_21821ed3a76ed395 FOREIGN KEY (user_id) REFERENCES public."user"(id) ON DELETE CASCADE;


--
-- Name: charte_agreement fk_3c434f6aa76ed395; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.charte_agreement
    ADD CONSTRAINT fk_3c434f6aa76ed395 FOREIGN KEY (user_id) REFERENCES public."user"(id);


--
-- Name: transaction fk_723705d18805ab2f; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.transaction
    ADD CONSTRAINT fk_723705d18805ab2f FOREIGN KEY (annonce_id) REFERENCES public.annonce(id);


--
-- Name: transaction fk_723705d1cd53edb6; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.transaction
    ADD CONSTRAINT fk_723705d1cd53edb6 FOREIGN KEY (receiver_id) REFERENCES public."user"(id);


--
-- Name: conversation fk_8a8e26e96c755722; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.conversation
    ADD CONSTRAINT fk_8a8e26e96c755722 FOREIGN KEY (buyer_id) REFERENCES public."user"(id);


--
-- Name: conversation fk_8a8e26e98805ab2f; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.conversation
    ADD CONSTRAINT fk_8a8e26e98805ab2f FOREIGN KEY (annonce_id) REFERENCES public.annonce(id);


--
-- Name: message fk_b6bd307f9ac0396; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.message
    ADD CONSTRAINT fk_b6bd307f9ac0396 FOREIGN KEY (conversation_id) REFERENCES public.conversation(id);


--
-- Name: message fk_b6bd307ff624b39d; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.message
    ADD CONSTRAINT fk_b6bd307ff624b39d FOREIGN KEY (sender_id) REFERENCES public."user"(id);


--
-- Name: notification fk_bf5476caa76ed395; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.notification
    ADD CONSTRAINT fk_bf5476caa76ed395 FOREIGN KEY (user_id) REFERENCES public."user"(id);


--
-- Name: annonce_image fk_d2b0cfc08805ab2f; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.annonce_image
    ADD CONSTRAINT fk_d2b0cfc08805ab2f FOREIGN KEY (annonce_id) REFERENCES public.annonce(id);


--
-- Name: annonce fk_f65593e512469de2; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.annonce
    ADD CONSTRAINT fk_f65593e512469de2 FOREIGN KEY (category_id) REFERENCES public.category(id);


--
-- Name: annonce fk_f65593e57a88e00; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.annonce
    ADD CONSTRAINT fk_f65593e57a88e00 FOREIGN KEY (locked_by_id) REFERENCES public."user"(id) ON DELETE SET NULL;


--
-- Name: annonce fk_f65593e57e3c61f9; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.annonce
    ADD CONSTRAINT fk_f65593e57e3c61f9 FOREIGN KEY (owner_id) REFERENCES public."user"(id);


--
-- Name: user_favorites fk_user_favorites_annonce; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.user_favorites
    ADD CONSTRAINT fk_user_favorites_annonce FOREIGN KEY (annonce_id) REFERENCES public.annonce(id) ON DELETE CASCADE;


--
-- Name: user_favorites fk_user_favorites_user; Type: FK CONSTRAINT; Schema: public; Owner: plateforme_user
--

ALTER TABLE ONLY public.user_favorites
    ADD CONSTRAINT fk_user_favorites_user FOREIGN KEY (user_id) REFERENCES public."user"(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict xOUqqZcyj71y3hF4UvbKSqWuVAoaZki0d7wKvyNRCDN89ZQMVY5VqAzfJeZjOwE


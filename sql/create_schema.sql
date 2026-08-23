create schema svenska;
use svenska;

create user 'svenska'@'127.0.0.1' identified by 'super-secure-password';
grant select ON svenska.* to 'svenska'@'127.0.0.1';
grant update,insert ON svenska.user to 'svenska'@'127.0.0.1';
grant update,insert ON svenska.audio to 'svenska'@'127.0.0.1';

create user 'svenska_admin'@'127.0.0.1' identified by 'super-duper-secure-password';
grant all privileges ON svenska.* to 'svenska_admin'@'127.0.0.1';

create table user
(
    id CHAR(36) DEFAULT (uuid()),
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    email varchar(255) not null,
    first_name varchar(255) null,
    middle_name varchar(255) null,
    last_name varchar(255) null,
    preferences JSON null,
    password_hash varchar(255) not null,
    admin bool default false,

    constraint user_pk
        primary key (id)
);

CREATE TABLE user_token
(
    user_id CHAR(36) NOT NULL,
    token varchar(255) not null,
    remote_address varchar(32) not null,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,

    constraint user_token_user_id_fk
        FOREIGN KEY (user_id) REFERENCES user (id),

    constraint user_token_pk
        primary key (user_id, token)
);

CREATE TABLE language (
    id varchar(16) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

CREATE TABLE category (
    id CHAR(36) DEFAULT (uuid()),
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

CREATE TABLE category_name (
    category_id CHAR(36) DEFAULT (uuid()),
    language_id varchar(16) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (category_id, language_id),
    constraint category_name_category_id_fk FOREIGN KEY (category_id) REFERENCES category(id),
    constraint category_name_language_id_fk FOREIGN KEY (language_id) REFERENCES language(id)
);

CREATE TABLE phrase (
    id CHAR(36) DEFAULT (uuid()),
    language_id varchar(16) NOT NULL,
    category_id CHAR(36) NOT NULL,
    text LONGTEXT NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    constraint phrase_language_id_fk FOREIGN KEY (language_id) REFERENCES language(id),
    constraint phrase_category_id_fk FOREIGN KEY (category_id) REFERENCES category(id)
);

CREATE TABLE translation (
    from_phrase_id CHAR(36),
    to_phrase_id CHAR(36),
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (from_phrase_id, to_phrase_id),
    constraint translation_from_phrase_id_fk FOREIGN KEY (from_phrase_id) REFERENCES phrase(id),
    constraint translation_to_phrase_id_fk FOREIGN KEY (to_phrase_id) REFERENCES phrase(id)
);

CREATE TABLE voice (
    id CHAR(36) NOT NULL,
    language_id varchar(16) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    constraint voice_language_id_fk FOREIGN KEY (language_id) REFERENCES language(id)
);

CREATE TABLE audio (
    id CHAR(36) DEFAULT (uuid()),
    phrase_id CHAR(36),
    voice_id CHAR(36),
    speed FLOAT NOT NULL DEFAULT 1.0,
    alignment JSON NULL,
    normalized_alignment JSON NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (phrase_id, voice_id, speed),
    constraint audio_phrase_id_fk FOREIGN KEY (phrase_id) REFERENCES phrase(id),
    constraint audio_voice_id_fk FOREIGN KEY (voice_id) REFERENCES voice(id)
);

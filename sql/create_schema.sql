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
    id VARCHAR(40) DEFAULT (uuid()),
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
    user_id VARCHAR(40) NOT NULL,
    token varchar(255) not null,
    remote_address varchar(32) not null,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,

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
    id VARCHAR(40) DEFAULT (uuid()),
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

CREATE TABLE category_name (
    category_id VARCHAR(40) DEFAULT (uuid()),
    language_id varchar(16) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (category_id, language_id),
    FOREIGN KEY (category_id) REFERENCES category(id),
    FOREIGN KEY (language_id) REFERENCES language(id)
);

CREATE TABLE phrase (
    id VARCHAR(40) DEFAULT (uuid()),
    language_id varchar(16) NOT NULL,
    category_id VARCHAR(40) NOT NULL,
    text LONGTEXT NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (language_id) REFERENCES language(id),
    FOREIGN KEY (category_id) REFERENCES category(id)
);

CREATE TABLE translation (
    from_phrase_id VARCHAR(40),
    to_phrase_id VARCHAR(40),
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (from_phrase_id, to_phrase_id),
    FOREIGN KEY (from_phrase_id) REFERENCES phrase(id),
    FOREIGN KEY (to_phrase_id) REFERENCES phrase(id)
);

CREATE TABLE voice (
    id VARCHAR(40) NOT NULL,
    language_id varchar(16) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (language_id) REFERENCES language(id)
);

CREATE TABLE audio (
    id VARCHAR(40) DEFAULT (uuid()),
    phrase_id VARCHAR(40),
    voice_id VARCHAR(40),
    speed FLOAT NOT NULL DEFAULT 1.0,
    alignment JSON NULL,
    normalized_alignment JSON NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (phrase_id, voice_id, speed),
    FOREIGN KEY (phrase_id) REFERENCES phrase(id),
    FOREIGN KEY (voice_id) REFERENCES voice(id)
);

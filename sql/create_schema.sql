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
        primary key (id),

    constraint user_email unique (email)
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
    id varchar(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id),

    constraint language_name unique (name)
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
    constraint category_name_language_id_fk FOREIGN KEY (language_id) REFERENCES language(id),
    constraint category_language_name unique (language_id, name)
);

CREATE TABLE ui_text (
    id CHAR(36) NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

CREATE TABLE ui_text_name (
    ui_text_id CHAR(36) NOT NULL,
    language_id varchar(16) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (ui_text_id, language_id),
    constraint ui_text_name_ui_text_id_fk FOREIGN KEY (ui_text_id) REFERENCES ui_text(id),
    constraint ui_text_name_language_id_fk FOREIGN KEY (language_id) REFERENCES language(id)
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

CREATE TABLE language_translation (
    from_language_id varchar(36) NOT NULL,
    to_language_id varchar(36) NOT NULL,
    created timestamp not null default CURRENT_TIMESTAMP,
    updated timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (from_language_id, to_language_id),
    constraint language_translation_from_language_id_fk FOREIGN KEY (from_language_id) REFERENCES language(id),
    constraint language_translation_to_language_id_fk FOREIGN KEY (to_language_id) REFERENCES language(id)
);

-- Modifications after creation

alter table category
    add priority int default 0 not null;

alter table translation
    drop foreign key translation_from_phrase_id_fk;

alter table translation
    add constraint translation_from_phrase_id_fk
        foreign key (from_phrase_id) references phrase (id)
            on delete cascade;

alter table translation
    drop foreign key translation_to_phrase_id_fk;

alter table translation
    add constraint translation_to_phrase_id_fk
        foreign key (to_phrase_id) references phrase (id)
            on delete cascade;

alter table audio
    drop foreign key audio_phrase_id_fk;

alter table audio
    add constraint audio_phrase_id_fk
        foreign key (phrase_id) references phrase (id)
            on delete cascade;

alter table category
    add parent_category_id char(36) null;

alter table category
    add constraint category_parent_category_id_fk
        foreign key (parent_category_id) references category (id);

alter table language
    add language_id char(36) not null,
    add priority int default 0 not null,
    add constraint language_unique
        unique (language_id, priority);

alter table voice
    add priority int default 0 not null,
    add constraint language_unique
        unique (language_id, priority);
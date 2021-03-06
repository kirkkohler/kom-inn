import React from 'react';

export default class Person extends React.Component {
    constructor(props) {
        super(props);
    }

    getAdults() {
        const { person } = this.props;

        const female = parseInt(person.adults_f, 10) || 0;
        const male = parseInt(person.adults_m, 10) || 0;

        return female + male;
    }

    getType() {
        const { person: { type } } = this.props;

        if (type === 'HOST') {
            return 'Host';
        } else {
            return 'Guest';
        }
    }

    statusText(c) {
        const t = {
            "-2" : "purged",
            "-1" : "expired",
            "0" : "new",
            "1" : "active",
            "2" : "used"
        }
        return t[c];
    }

    render() {
        const { person, handleRemove } = this.props;

        return <div className="person">
            <span className="title">{this.getAdults()} adults. {person.children} children.</span>
            <span className="status">{this.statusText(person.status)}</span>
            <div className={'info'}>
                <span className="name">{person.name}</span>
                <span className="info">{person.age} år. {person.adults_f} females. {person.adults_m} males. {person.children} children. {this.getType()}.</span>
                <span className="origin">{person.origin}.</span> <br />
                <span className="phone">Phone: <a href={`tel:${person.phone}`}>{person.phone}</a></span> <span className="email">Email <a href={`mailto:${person.email}`}>{person.email}</a></span> <br />
                <span className="address">{person.address} {person.zipcode}</span> <br />
                <span className="bringing">{person.bringing || <i>No people description</i>}</span> <br />
                <span className="freetext">{person.freetext || <i>No description</i>}</span> <br />
                {this.props.convertPerson ?
                    <a href="#" onClick={() => this.props.convertPerson(person.id)}>Change person to other type</a> : null
                }
                <br />
                {this.props.removePerson ?
                    <a href="#" onClick={() => this.props.removePerson(person.id)}>Remove person from database</a> : null
                }
            </div>
        </div>
    }
}
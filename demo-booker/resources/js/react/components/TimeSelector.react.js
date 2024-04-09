const React = require('react');
const TimeOptions = require('../constants/TimeOptions');

class TimeSelector extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            value: this.props.fieldValue || 'none',
        };
    }

    getTimeOptions() {
        const modifiedOptions = Object.assign({}, TimeOptions);
        const output = [];

        Object.keys(modifiedOptions).forEach((key) => {
            if(
                key < parseInt(this.props.startLimit)
                || key > parseInt(this.props.endLimit)
            ) {
                delete modifiedOptions[key];
            }
        });

        Object.keys(modifiedOptions).forEach((key) => {
            output.push(
                <option key={key} value={key}>{modifiedOptions[key]}</option>
            );
        });

        return output;
    }

    render() {
        const {
            onChange,
            fieldName,
            fieldKey,
            fieldValue,
            disabled,
        } = this.props;

        return (
            <select
                name={fieldName}
                onChange={(e) => onChange(fieldKey, e.target.value)}
                value={fieldValue}
                disabled={disabled}
            >
                <option value="">None</option>
                {this.getTimeOptions()}
            </select>
        );
    }
}

module.exports = TimeSelector;
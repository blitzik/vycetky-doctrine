<?php

namespace Exceptions\Logic;

    class LogicException extends \LogicException {}
    
    class InvalidArgumentException extends LogicException {}
    
    class LengthException extends LogicException {}

    class InvalidMessageTypeException extends LogicException {}

    class MissingRequiredArrayMemberException extends LogicException {}

	class InvalidTimeFormatException extends LogicException {}

    class TimeConverterMissingException extends LogicException {}
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Props = {
    url: string | null;
    name: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function AvatarPreview({ url, name, open, onOpenChange }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{name}</DialogTitle>
                    <DialogDescription className="sr-only">
                        Profile photo preview
                    </DialogDescription>
                </DialogHeader>

                {url && (
                    <img
                        src={url}
                        alt={`${name}'s profile photo`}
                        className="mx-auto aspect-square w-full max-w-md rounded-xl object-cover"
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}
